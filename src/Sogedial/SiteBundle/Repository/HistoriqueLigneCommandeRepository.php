<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * HistoriqueLigneCommandeRepository
 *
 */
class HistoriqueLigneCommandeRepository extends EntityRepository
{
    public function getHistoriqueLigneCommandeByCommandeId($commandeId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('hlc')
            ->from('SogedialSiteBundle:HistoriqueLigneCommande', 'hlc')
            ->innerJoin('SogedialSiteBundle:LigneCommande', 'lc',
                'WITH', 'lc.id = hlc.ligneCommande')
            ->innerJoin('SogedialSiteBundle:Commande', 'c',
                'WITH', 'c.id = lc.commande AND c.id = :commande')
            ->setParameter("commande", $commandeId);

        return $qb->getQuery()->getResult();
    }
}
