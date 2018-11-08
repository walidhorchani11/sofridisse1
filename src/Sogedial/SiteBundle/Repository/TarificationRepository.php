<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class TarificationRepository extends EntityRepository
{
    /**
     * @param $code
     * @param $libelle
     */
    public function updateTarification($code, $libelle)
    {
        $qb = $this->createQueryBuilder('tar');

        $qb->update('SogedialSiteBundle:Tarification tar')
            ->set('tar.code', $code)
            ->set('tar.libelle', $libelle)
            ->getQuery()->execute();
    }

    public function getListTarificationsByRegionForProspect($region){
        $qb = $this->createQueryBuilder('tar');

        $qb->add('select', 'tar')
            ->add('from', 'SogedialSiteBundle:Tarification tar')
            ->innerJoin('SogedialSiteBundle:Tarif','t',\Doctrine\ORM\Query\Expr\Join::WITH, 't.tarification = tar.code')
            ->where("t.code LIKE '".$region."%'");
        
        return $qb;        
    }

    /**
     * @return array
     */
    public function getListTarification()
    {
        $qb = $this->createQueryBuilder('tar');

        $qb->add('select', 'tar')
            ->add('from', 'SogedialSiteBundle:Tarification tar');

        $result = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }
}