<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use  Sogedial\SiteBundle\Entity\LigneCommande;
use  Sogedial\UserBundle\Entity\User;
use  Sogedial\SiteBundle\Entity\HistoriqueLigneCommande;

class HistoriqueLigneCommandeService
{
    /**
     * @var EntityManager
     */
    private $em;
    private $repository;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository('SogedialSiteBundle:HistoriqueLigneCommande');
    }

    public function getHistoriqueLigneCommandeByCommandeId($commandeId)
    {
        $historyOrderProducts = $this->repository->getHistoriqueLigneCommandeByCommandeId($commandeId);
        $products = array();
        
        foreach($historyOrderProducts as $history){
            $ligne = $history->getLigneCommande()->getId();
            if(!array_key_exists($ligne, $products)){
                $products[$ligne] = array();
            }
            array_push($products[$ligne], $history);
        }

        return $products;
    }

    /**
     * @param Product $product
     * @param Client $client
     * @return mixe
     */
    public function create(LigneCommande $commandLine, User $user, $quantity)
    {
        $historiqueCommandLine = new HistoriqueLigneCommande();

        $historiqueCommandLine->setQuantite($quantity);
        $historiqueCommandLine->setModifier($user);
        $historiqueCommandLine->setLigneCommande($commandLine);

        return $historiqueCommandLine;
    }
}