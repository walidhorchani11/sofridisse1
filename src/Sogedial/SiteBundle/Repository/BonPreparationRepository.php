<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BonPreparationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BonPreparationRepository extends EntityRepository{
    /**
    * @param commande integer, is an id of a catalogue.commande
    */
    public function getSumColisFacturation($commande){
        $qb = $this->createQueryBuilder("bp");
 
        $qb
            ->select('sum(bp.colisFacture) as sumColisFacture')
            ->where('bp.commande = :commande')
            ->setParameters(array("commande" => $commande));

        return current($qb->getQuery()->getResult())["sumColisFacture"];
    }

    public function getSumMontantFacturation($commande){
        $qb = $this->createQueryBuilder("bp");
 
        $qb
            ->select('sum(bp.montantFacturation) as sumMontantFacture')
            ->where('bp.commande = :commande')
            ->setParameters(array("commande" => $commande));

        return current($qb->getQuery()->getResult())["sumMontantFacture"];

    }
}