<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class RayonRepository extends EntityRepository
{
    /**
     * @param $codeSecteur
     * @return array
     */
    public function getAllRayonsOfSecteur($codeSecteur) {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb
            ->select('r.code, r.libelle')
            ->from('SogedialSiteBundle:Rayon', 'r')
            ->where('r.secteur = :codeSecteur')
            ->orderBy('r.libelle', 'ASC')
            ->setParameter('codeSecteur', $codeSecteur)
            ->getQuery()
            ->getArrayResult()
        ;

        return $result;
    }
}
