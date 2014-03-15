<?php

namespace OM\APIBundle\Entity;

use Doctrine\ORM\Query;

class MessageModelManager extends BaseModelManager
{
    const WIDGET_ALL = 0;
    const WIDGET_DIALOG = 1;

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
                $q = "SELECT m FROM OM\\APIBundle\\Entity\\Message m
                    LEFT JOIN m.fromUser f
                    LEFT JOIN m.toUser t";
                break;
            case 'count':
                $q = "SELECT COUNT(m.id) FROM OM\\APIBundle\\Entity\\Message m";
                break;
            default:
                throw new \Exception('Wrong query type: '.$type);
        }
        $order = "ORDER BY m.created";
        switch ($params['type']) {
            default:
            case self::WIDGET_ALL:
                $query = $this->em->createQuery("$q $order");
                break;
            case self::WIDGET_DIALOG:
                $where = "WHERE m.from_id IN (:with) AND m.to_id IN (:with)";
                $query = $this->em->createQuery("$q $where $order");
                $query->setParameter('with', $params['with']);
                break;
        }

        return $query;
    }

    protected function widgetFormat($data, $params)
    {
        $rows = array();
        /** @var Message $msg */
        foreach ($data as $msg) {
            $text = $msg->getText();
            if (isset($params['textLength'])) {
                $text = substr($text, 0, $params['textLength']);
            }
            $rows[] = array(
                'id' => $msg->getId(),
                'text'=> $text,
                'from' => array(
                    'id' => $msg->getFromUser()->getId(),
                    'username' => $msg->getFromUser()->getUsername()
                ),
                'to' => array(
                    'id' => $msg->getToUser()->getId(),
                    'username' => $msg->getToUser()->getUsername()
                )
            );
        }
        return $rows;
    }

}
