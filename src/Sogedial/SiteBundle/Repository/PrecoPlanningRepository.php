<?php

namespace Sogedial\SiteBundle\Repository;


use Doctrine\ORM\EntityRepository;


class PrecoPlanningRepository extends EntityRepository
{
    /**
     * @param $codeSociete
     * @return array
     */
    public function getListBateauDate($codeSociete)
    {
        $params = array(
            'codeSociete' => $codeSociete
        );

        $qb = $this->createQueryBuilder('pb');
        $qb->select('pb')
            ->where('pb.identifiantSociete = :codeSociete')
            ->addOrderBy('pb.id', 'ASC')
            ->setParameters($params)
        ;

        return $qb->getQuery()->getResult();
    }
}