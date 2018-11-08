<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SousFamilleRepository extends EntityRepository
{
    /**
     * @param $codeFamille
     * @return array
     */
    public function getAllSousFamillesOfFamille($codeFamille) {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb
            ->select('sf.code, sf.libelle')
            ->from('SogedialSiteBundle:SousFamille', 'sf')
            ->where('sf.famille = :codeFamille')
            ->orderBy('sf.libelle', 'ASC')
            ->setParameter('codeFamille', $codeFamille)
            ->getQuery()
            ->getArrayResult()
        ;

        return $result;
    }
}