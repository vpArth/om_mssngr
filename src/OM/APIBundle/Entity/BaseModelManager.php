<?php

namespace OM\APIBundle\Entity;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use \Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\Container;

abstract class BaseModelManager
{

    protected $class;
    /** @var EntityManager */
    protected $em;
    /** @var EntityRepository */
    protected $repository;
    /** @var Container */
    protected $container;

    /**
     * Constructor.
     *
     * @param EntityManager  $em
     * @param string   $class
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->repository = $em->getRepository($class);
        $metadata = $em->getClassMetadata($class);
        $this->class = $metadata->name;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function getDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }

    /**
     * Create model object
     *
     * @return Entity
     */
    public function create()
    {
        $class = $this->getClass();
        return new $class;
    }

    /**
     * Persist the model
     *
     * @param $model
     * @param boolean $flush
     * @return Entity
     */
    public function save($model, $flush = true)
    {
        $this->em->persist($model);
        if ($flush) {
            $this->em->flush();
            $this->cacheModelSave($model);
        }
        return $model;
    }

    /**
     * Delete a model.
     *
     * @param Entity $model
     * @param bool $flush
     */
    public function delete($model, $flush = true)
    {
        $id = $model->getId();
        $this->em->remove($model);
        if ($flush) {
            $this->em->flush();
            $this->cacheModelDel($id);

        }
    }
    /**
     * Reload the model data.
     */
    public function reload($model)
    {
        $this->em->refresh($model);
    }

    /**
     * Returns the user's fully qualified class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    protected function getCacheName($id)
    {
        return "orm:cache:" . $this->getClass() . ":" . $id;
    }

    protected function getWidgetCacheName($params)
    {
        return "widget:" . $this->getClass() . ":" . json_encode($params);
    }

    protected function cacheModelSave(\Serializable $model)
    {
        $id = $model->getId();
        $key = $this->getCacheName($id);
        $this->saveCache($key, serialize($model), 15);
    }

    protected function cacheModelGet($id)
    {
        $key = $this->getCacheName($id);
        $data = $this->getCache($key);
        if ($data) {
            return unserialize($data);
        }
        return false;
    }

    protected function cacheModelDel($id)
    {
        $key = $this->getCacheName($id);
        $this->delCache($key);
    }

    // Cache interface:

    protected function saveCache($key, $data, $time = 15)
    {
        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        $cache->save($key, $data, $time);
    }

    protected function getCache($key)
    {
        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        try {
            if ($cache->contains($key)) {
                return $cache->fetch($key);
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    protected function delCache($key)
    {
        /** @var Cache $cache */
        $cache = $this->getContainer()->get('aequasi_cache.instance.default');
        $cache->delete($key);
    }

    /**
     * @param $id
     * @return Entity
     */
    public function find($id)
    {
        if ($model = $this->cacheModelGet($id)) {
        } else {
            $model = $this->findBy(array('id' => $id));
        }
        if ($model) {
            $this->cacheModelSave($model);
        }

        return $model;
    }

    /**
     * @param $q
     * @return Entity
     */
    public function findBy($q)
    {
        return $this->repository->findOneBy($q);
    }

    public function isDebug()
    {
        return $this->container->get('kernel')->isDebug();
    }

    /**
     * @param string $type
     * @param array $params
     * @return Query
     */
    abstract protected function getWidgetQuery($type = 'rows', $params = array());
    abstract protected function widgetFormat($data, $params);

    public function widget($params)
    {
        $key = $this->getWidgetCacheName($params);
        $result = $this->getCache($key);
        if ($result) {
            $result = unserialize($result);
            //$this->saveCache($key, serialize($result), 15); // can became wrong
            return $result;
        }
        $page = isset($params['page']) ? $params['page'] : 0;
        $size = isset($params['size']) ? $params['size'] : 5;
        /** @var Query $query */
        $query = $this->getWidgetQuery('rows', $params);
        $countQuery = $this->getWidgetQuery('count', $params);
        $query = $query->setFirstResult($page * $size)
            ->setMaxResults($size);

        $count = array_sum(array_map('current', $countQuery->getScalarResult()));

        $rows = $count
            ? $this->widgetFormat($query->getResult(), $params)
            : array();
        $result = array(
            'rows' => $rows,
            'count' => $count,
            'page' => $page,
            'size' => $size
        );
        $this->saveCache($key, serialize($result), 15);
        return $result;
    }
}
