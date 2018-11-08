<?php

namespace Sogedial\SiteBundle\Service;


use Doctrine\ORM\Mapping\Entity;

use Doctrine\ORM\EntityManager;

abstract class AbstractService extends AbstractLogger
{

    const BUNDLE_NAME = 'SogedialSiteBundle';
    
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    public function setEntityManager(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * Retourne l'entity manager
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->entityManager;
    }


    /**
     * Retourne le repository de l'entité
     * 
     * @return Doctrine\ORM\EntityRepository
     */
    protected function getRepository() {
        $result = null;
        $entityName = $this->getEntityName();
        if (null !== $entityName) {
            $result = $this->getEntityManager()
                    ->getRepository(self::BUNDLE_NAME . ':' . $entityName);
        }
        return $result;
    }

    /**
     * Retourne le nom de l'entité
     * 
     * @return string
     */
    protected function getEntityName()
    {
        //traitement en fonction du namespace
        $tab = explode('\\', get_class($this));
        return end($tab);
    }

    /**
     * Find one by id
     * 
     * @param int $id
     */
    public function get($id) {
        return $this->getRepository()->find($id);
    }

    /**
     * Fetch All
     * 
     * @param array $order
     * @param int $limit
     */
    public function getAll($order = array(), $limit = null) {
        return $this->getRepository()->findBy(array(), $order, $limit);
    }

    /**
     * Save a entity
     * 
     * @param Entity $entity
     * @param boolean $merge
     * @param boolean $flush
     */
    public function save($entity, $merge=false, $flush=true) {

        if (method_exists($entity, 'getId') && null === $entity->getId() && true === method_exists($entity, 'setDateCreate')){
                $entity->setDateCreate(new \DateTime());
        } else if (true === method_exists($entity, 'setDateUpdate')){
                $entity->setDateUpdate(new \DateTime());
        }
        if (true ===  $merge) {
            $this->getEntityManager()->merge($entity);
        } else { 
            $this->getEntityManager()->persist($entity);
        }
        if (true == $flush) {
            $this->getEntityManager()->flush();
        }
        return $entity;
    }

    public function saveArray($data, $merge=false)
    {
        foreach($data as $entity){
            $this->save($entity, $merge,  false);
        }
        $this->getEntityManager()->flush();
        return $data;
    }
    
    public function delete($entity)
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        return $entity;
    }
    
    public function deleteById($id)
    {
        $entity = $this->get($id);
        return $this->delete($entity);
    }

}


