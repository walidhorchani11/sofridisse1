<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * MarqueRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MarqueRepository extends EntityRepository
{
    /**
     * Get all marque
     *
     * @return array
     */
    public function getListMarques()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->add('select', 'm')
            ->add('from', 'SogedialSiteBundle:Marque m');
        $results = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        return $results;

    }
}