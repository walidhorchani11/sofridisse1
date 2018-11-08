<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Sogedial\SiteBundle\Entity\Client;
use Doctrine\ORM\Query;

/**
 * MoreStockRequestRepository
 *
 */
class MoreStockRequestRepository extends EntityRepository
{
    public function getByClient(Client $client){
        $qb = $this->_em->createQueryBuilder();   

        $qb->select('msr');
        $qb->from('SogedialSiteBundle:MoreStockRequest', 'msr');
        $qb->where('msr.client = :client');
        $qb->setParameter('client', $client);

        return $qb->getQuery()->getResult();
    }

    public function deleteByClient(Client $client){
        $qb = $this->_em->createQueryBuilder();   
        
        $qb->delete('SogedialSiteBundle:MoreStockRequest', 'msr');
        $qb->where('msr.client = :client');
        $qb->setParameter('client', $client);
        
        return $qb->getQuery()->execute();
    }
}
