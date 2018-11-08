<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class JoursFeriesRepository extends EntityRepository
{
    /**
     * @param $codeSociete
     * @return string
     */
    public function getJourFerieByCodeSociete($codeSociete)
    {
        $arrayHoliday = array();
        $qb = $this->createQueryBuilder('jf');

        $qb->select('jf.monthNumber, jf.dayNumber, jf.countryCode')
            ->where('jf.entreprise = :codeSociete');
        $qb->setParameter('codeSociete', $codeSociete);

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $result) {
            $arrayHoliday[] = "[" . implode(", ", $result) . "]";
        }

        $arrayString = sprintf('%s%s%s', '[',  implode(',', $arrayHoliday), ']');

        return $arrayString;


    }
}