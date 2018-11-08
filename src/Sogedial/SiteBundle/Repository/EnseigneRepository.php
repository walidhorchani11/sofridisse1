<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class EnseigneRepository extends EntityRepository
{
    /**
     * @param $code
     * @param $libelle
     */
    public function updateEnseigne($code, $libelle)
    {
        $qb = $this->createQueryBuilder('ens');

        $qb->update('SogedialSiteBundle:Enseigne ens')
            ->set('ens.code', $code)
            ->set('ens.libelle', $libelle)
            ->getQuery()->execute();
    }

    /**
     * @return array
     */
    public function getListEnseigne()
    {
        $qb = $this->createQueryBuilder('ens');

        $qb->add('select', 'ens')
            ->add('from', 'SogedialSiteBundle:Enseigne ens');

        $result = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }

    public function getListEnseignesByRegion($region)
    {
        $qb = $this->createQueryBuilder('ens');

        $qb->add('select', 'ens')
            ->add('from', 'SogedialSiteBundle:Enseigne ens')
            ->where("ens.code LIKE '" . $region . "-%'");

        return $qb;
    }

    public function getListEnseignesByRegionForProspect($region)
    {
        $qb = $this->createQueryBuilder('ens');

        $qb->add('select', 'ens')
            ->add('from', 'SogedialSiteBundle:Enseigne ens')
            ->where("ens.code LIKE '" . $region . "-%'")
            ->andWhere("ens.for_prospect IS NOT NULL");

        return $qb;
    }

    public function getCodeSansEnseigneByRegion($region)
    {
        return $region . '-SE$';
    }
}