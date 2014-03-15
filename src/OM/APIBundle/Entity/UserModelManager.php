<?php

namespace OM\APIBundle\Entity;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Query;
use OM\APIBundle\Helper\Validation;

class UserModelManager extends BaseModelManager
{
    /**
     * @param User|integer $userId
     * @param integer $period Default - 5 min
     */
    public function updateOnline($userId, $period = 300)
    {
        if ($userId instanceof User) {
            $userId = $userId->getId();
        }

        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        /** @var integer $userId */
        $key = "onlineState:" . $userId;
        $cache->save($key, 1, $period);
    }

    /**
     * @param User|integer $userId
     * @return bool
     */
    public function isOnline($userId)
    {
        if ($userId instanceof User) {
            $userId = $userId->getId();
        }

        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        /** @var integer $userId */
        $key = "onlineState:" . $userId;
        return $cache->contains($key);
    }

    /**
     * @param User $user
     * @return string $token
     */
    public function login(User $user)
    {
        //last login update
        $user->setLastLogin(time());
        $this->save($user);

        // auth token
        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        $token = md5(uniqid().microtime(1));
        $key = "authToken:" . $token;
        $cache->save($key, $user->getId(), 24*3600);
        return $token;
    }

    /**
     * @param $token
     * @return $this
     */
    public function logout($token)
    {
        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        $key = "authToken:" . $token;
        $cache->delete($key);
        return $this;
    }

    const WIDGET_ALL = 0;

    /**
     * @param string $type
     * @param array $params
     * @throws \Exception
     * @internal param int $modfication
     * @return Query
     */
    protected function getWidgetQuery($type = 'rows', $params = array())
    {
        switch ($type) {
            case 'rows':
                $q = "SELECT u FROM OM\\APIBundle\\Entity\\User u";
                break;
            case 'count':
                $q = "SELECT COUNT(u.id) FROM OM\\APIBundle\\Entity\\Message u";
                break;
            default:
                throw new \Exception('Wrong query type: '.$type);
        }
        switch ($params['type']) {
            default:
            case self::WIDGET_ALL:
                $query = $this->em->createQuery($q);
                break;
        }
        return $query;
    }

    protected function widgetFormat($data, $params)
    {
        $rows = array();
        /** @var User $row */
        foreach ($data as $row) {
            $values = $row->getValues();
            if (isset($params['fields'])) {
                $r = array();
                foreach ($params['fields'] as $f) {
                    $r[$f] = $values[$f];
                }
                $r['online'] = $this->isOnline($row);
                $rows[] = $r;
            } else {
                $rows[] = $values;
            }
        }
        return $rows;
    }

    public function getPublicProfile($id)
    {
        /** @var User $user */
        $user = $this->find($id);
        if (!$user) {
            throw new \Exception("Requested user inactive or not exists", Validation::USER_NOT_FOUND);
        }
        $result = array();
        $values = $user->getValues();
        foreach (array('id', 'username', 'email', 'lastLogin') as $field) {
            $result[$field] = $values[$field];
        }
        $result['online'] = $this->isOnline($user);
        return $result;
    }
}
