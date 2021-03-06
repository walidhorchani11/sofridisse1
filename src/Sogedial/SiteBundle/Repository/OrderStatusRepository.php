<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * OrderStatusRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderStatusRepository extends EntityRepository
{
    public function  getOrderStatusKeyByOrder($order) {

        $qb = $this->_em->createQueryBuilder();

        $qb->select('os.key')
            ->from('SogedialSiteBundle:OrderStatus','os')
            ->leftJoin('Sogedial\SiteBundle\Entity\OrderOrderStatus', 'oos',
                \Doctrine\ORM\Query\Expr\Join::WITH, 'oos.orderStatus = os')
            ->where('oos.order = :order');

        $qb->setParameter('order', $order);

        return $qb->getQuery()->getSingleScalarResult();
    }


}
