<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * ProduitRegleRepository
 */
class ProduitRegleRepository extends EntityRepository
{
    /**
     * @param $productCode
     * @return boolean
     */
    public function doesProductHaveMoq($productCode)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('pr')
            ->from('SogedialSiteBundle:ProduitRegle', 'pr')
            ->where('pr.code = :code')
            ->setParameter('code', $productCode);

        try {
            return ($qb->getQuery()->getSingleResult() != NULL);
        } catch (NoResultException $e) {
            return false;
        }
    }
}