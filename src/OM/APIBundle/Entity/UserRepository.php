<?php

namespace OM\APIBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    private static function getField($name, $param = null)
    {
        switch ($name)
        {
            case 'online':
                return "
CASE WHEN
    CASE WHEN u.lastLogin IS NULL
    THEN 0
    ELSE UNIX_TIMESTAMP()-u.lastLogin-{$param}
    END  >= 0
THEN 0
ELSE 1
END online";
            case 'messages':
                return "0 messages";
        }
        return "1";
    }

    public function userList($params)
    {
        $qb = $this->createQueryBuilder('u');
        $page = isset($params['page']) ? $params['page'] : 0;
        $size = isset($params['size']) ? $params['size'] : 5;
        $select = array();
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $field) {
                $select[] = "u.{$field}";
            }
        }
        $select[] = self::getField("online", 600);
        $qb->select($select);
        if ($params['exclude_id']) {
            $qb->where('u.id != :id')
                ->setParameter('id', $params['exclude_id']);
        }
        $qb->setFirstResult($page*$size)
           ->setMaxResults($size);

        return $qb->getQuery()
            ->getResult();
    }

    public function getProfile($id, $params)
    {
        $qb = $this->createQueryBuilder('u');
        $select = array();
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $field) {
                $select[] = "u.{$field}";
            }
        }
        $select[] = self::getField("online", 600);
        $select[] = self::getField("messages");
        $qb->select($select);
        $qb->where('u.id = :id AND u.isActive = 1')
            ->setParameter('id', $id);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function updateLastLogin(User $user)
    {
        $user->setLastLogin(time());
        $this->_em->persist($user);
        $this->_em->flush();
        return $this;
    }
}
