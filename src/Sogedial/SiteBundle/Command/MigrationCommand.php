<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Command\ImporterCommands;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\BonPreparation;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Sogedial\SiteBundle\Entity\OrderStatus;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class MigrationCommand extends ImporterManager
{
    protected function configure()
    {
        $this
            ->setName('sogedial:migrate')
            ->setDescription('Migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::setup($input, $output, 0, 'migration');
//        $this->setDemandesEtColisCommandes();
//        $this->setOrderCommandeStatus();
//        $this->setEtatFOSUser();
        $this->setLignesCommandes();
    }

    public function setDemandesEtColisCommandes(){
        //$qb = parent::$em->getRepository("SogedialSiteBundle:Commande")->setDemandesEtColisCommandes();
        //\Doctrine\Common\Util\Debug::dump($qb);

        $qb = parent::$em->createQueryBuilder();

        $query = 'SELECT 
            o AS commande,
            sum(lc.montantTotal) AS montantTotal, 
            sum(lc.quantite) AS quantite
        FROM SogedialSiteBundle:Commande o
        LEFT JOIN SogedialSiteBundle:LigneCommande lc WITH lc.commande = o.parent AND lc.temperatureProduit = o.temperatureCommande
        WHERE
        o.parent IS NOT NULL AND
        (o.montantCommande IS NULL OR o.demandeColis IS NULL)
        GROUP BY o.id';

        $result = parent::$em
            ->createQuery($query)
            ->getResult();
        

        foreach($result as $key){
            $commande = $key["commande"];
            $user = $key["commande"]->getUser();
            $client = $key["commande"]->getClient();
            $montantTotal = $key["montantTotal"];
            $quantite = $key["quantite"];

            if($user !== null){
                $commande->setEntreprise($user->getEntreprise());
            } elseif($client !== NULL){
                $commande->setEntreprise($client->getEntreprise());
            }

            $commande->setMontantCommande($montantTotal);
            $commande->setDemandeColis($quantite);
            parent::$em->persist($commande);
        }
        $this->finish();
    }

    public function setOrderCommandeStatus(){
        $qb = parent::$em->createQueryBuilder();
        $query = 'UPDATE SogedialSiteBundle:OrderOrderStatus AS oss SET oss.orderStatus = \'3\' WHERE oss.orderStatus = \'2\'';

        parent::$em->createQuery($query)->getResult();
        $this->finish();
    }

    public function setEtatFOSUser(){
        $qb = parent::$em->createQueryBuilder();
        $query = 'UPDATE SogedialUserBundle:User AS u SET u.etat = \'client\' WHERE u.entreprise IS NOT NULL AND u.meta IS NOT NULL AND u.zone IS NOT NULL';

        parent::$em->createQuery($query)->getResult();
        $this->finish();
    }

    public function setLignesCommandes()
    {
        $qb = parent::$em->createQueryBuilder();

        $query = 'SELECT lc
        FROM SogedialSiteBundle:LigneCommande lc
        INNER JOIN SogedialSiteBundle:Commande c WITH lc.commande = c
        INNER JOIN SogedialSiteBundle:OrderOrderStatus oos WITH oos.order = c AND oos.orderStatus = 6';

        $result = parent::$em
            ->createQuery($query)
            ->getResult();

        foreach($result as $ligneCommande){
            $ligneCommande->setDenominationProduitBase($ligneCommande->getProduit()->getDenominationProduitBase());
            $ligneCommande->setPoidsVariable($ligneCommande->getProduit()->getPoidsVariable());
            $ligneCommande->setSaleUnity($ligneCommande->getProduit()->getSaleUnity());
            $ligneCommande->setPcb($ligneCommande->getProduit()->getPcb());
            $ligneCommande->setEan13($ligneCommande->getProduit()->getEan13());
            $ligneCommande->setTemperature($ligneCommande->getProduit()->getTemperature());
            $ligneCommande->setMarketingCode($ligneCommande->getProduit()->getMarketingCode());
            $ligneCommande->setNatureCode($ligneCommande->getProduit()->getNatureCode());
            $ligneCommande->setMarque($ligneCommande->getProduit()->getMarque());
            $ligneCommande->setFamille($ligneCommande->getProduit()->getFamille());
            $ligneCommande->setRayon($ligneCommande->getProduit()->getRayon());
            $ligneCommande->setActif(true);

            parent::$em->persist($ligneCommande);
            parent::$em->flush();
        }

    } 
}