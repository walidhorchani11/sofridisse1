<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SecteurRepository extends EntityRepository
{
    /**
     * @param $codeRegion
     * @return array
     */
    public function getAllSecteursOfRegion($codeRegion) {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb
            ->select('s.code, s.libelle')
            ->from('SogedialSiteBundle:Secteur', 's')
            ->where('s.code LIKE :codeRegion')
            ->orderBy('s.libelle', 'ASC')
            ->setParameter('codeRegion', $codeRegion.'-%')
            ->getQuery()
            ->getArrayResult()
        ;

        return $result;
    }
}