<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class DepartementRepository extends EntityRepository
{
    public function getLitDepartement()
    {
        $qb = $this->createQueryBuilder('d');
        $qb->add('select', 'd')
            ->add('from', 'SogedialSiteBundle:Departement d');

        $result = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);

        return $result;

    }
}