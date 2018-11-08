<?php


namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TarifRepository extends EntityRepository
{
    public function getTarifByEntrepriseId($entrepriseId)
    {
        return $this
            ->createQueryBuilder('t')
            ->select('t')
            ->where('t.id = :entrepriseId')
            ->setParameter('id', $entrepriseId)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }
}