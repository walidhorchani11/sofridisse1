<?php
namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\OrderStatus;
use Sogedial\SiteBundle\Entity\ProduitCompteur;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;
use Sogedial\SiteBundle\Service\UserInfo;

class DatabaseCountProductsCommand extends ImporterManager
{

    private $entreprises = [];

    protected function configure()
    {
        parent::configureCmd(
            'sogedial:setupProduitCompteurTable',
            'Setup produits_compteur table'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln('<comment>Start (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
        $output->writeln('Un peu de patiente...');

        $this->setup($input, $output, 0, "internal tables");
        $this->setSkipped(-1);

        $this->setProduitsCompteurs($input, $output);
        $this->setPromotionCompteur($input, $output);


        $now = new \DateTime();
        $output->writeln('<comment>End (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    private function getEntrepriseObjectByCode($code){
        if(!array_key_exists($code, $this->entreprises)){
          $this->entreprises[$code] = parent::$em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($code);  
        }
        return $this->entreprises[$code];
    }

    private function query($t)
    {   
        $targetTable = "";
        $target = "";
        switch($t){
            case 'r' :
                $targetTable = 'Rayon';
                $target = "rayon";
            break;
            case 's' :
                $targetTable = 'Secteur';
                $target = "secteur";
            break;
            case 'f' :
                $targetTable = 'Famille';
                $target = "famille";
            break;
        }

        if($t === 'g'){
            $query = "SELECT e.code as entreprise, a.valeur as assortiment, tf.code as tarification, ens.code as enseigne, count(p.code) as general
            FROM SogedialSiteBundle:Produit p
            INNER JOIN SogedialSiteBundle:Assortiment a WITH a.produit = p
            INNER JOIN SogedialSiteBundle:Entreprise e WITH a.entreprise = e
            LEFT JOIN SogedialSiteBundle:Tarif t1 WITH t1.produit = p
            AND p.entreprise = t1.entreprise

            LEFT JOIN SogedialSiteBundle:Tarif t2 WITH t2.produit = p
            AND p.entreprise = t2.entreprise

            LEFT JOIN SogedialSiteBundle:Tarification tf WITH t1.tarification = tf
            LEFT JOIN SogedialSiteBundle:Enseigne ens WITH t2.enseigne = ens
            WHERE a.entreprise IS NOT NULL
            GROUP BY a.valeur, tf.code, ens.code
            ";
            //
            //ORDER BY a.valeur ASC, a.entreprise ASC 

            $em = $this->getContainer()->get('doctrine')->getManager();
            $em->createQueryBuilder();
            $results = $em->createQuery($query)->getScalarResult();
            $len = count($results);

            for($i = 0; $i < $len; $i++){
                if($results[$i]["tarification"] !== NULL){
                    $tarification = "t.tarification = '" . $results[$i]["tarification"] . "'";
                } else {
                    $tarification = "t.enseigne = '" . $results[$i]["enseigne"] . "'";
                }
                $queryNew = "SELECT COUNT(p5.code) as general, count(p2.code) as nouveautes, count(p3.code) as avion, count(p4.code) as bateau
                            FROM SogedialSiteBundle:Produit p
                            INNER JOIN SogedialSiteBundle:Entreprise e WITH e.code = ".$results[$i]["entreprise"]."
                            INNER JOIN SogedialSiteBundle:Tarif t WITH t.produit=p AND t.entreprise = e AND $tarification                          
                            INNER JOIN SogedialSiteBundle:Assortiment a WITH a.produit=p AND a.entreprise = e AND a.valeur = '" . $results[$i]["assortiment"] . "'
                            LEFT JOIN SogedialSiteBundle:Produit p2 WITH p2 = p AND p2.natureCode = 'NOUVEAUTE'
                            LEFT JOIN SogedialSiteBundle:Produit p3 WITH p3 = p AND p3.preCommande = 1 AND p3.marketingCode LIKE 'AVION%'
                            LEFT JOIN SogedialSiteBundle:Produit p4 WITH p4 = p AND p4.preCommande = 1 AND p4.marketingCode NOT LIKE 'AVION%'
                            LEFT JOIN SogedialSiteBundle:Produit p5 WITH p5 = p AND p5.preCommande = 0
                            INNER JOIN SogedialSiteBundle:Stock s WITH s.produit=p
                            WHERE p.actif = 1
                            ";

                $em->createQueryBuilder();
                $resultNews = $em->createQuery($queryNew)->getResult();
                $results[$i]["general"] = $resultNews[0]["general"];
                $results[$i]["avion"] = $resultNews[0]["avion"];
                $results[$i]["bateau"] = $resultNews[0]["bateau"];
                $results[$i]["code"] = 0;
                $results[$i]["nouveautes"] = $resultNews[0]["nouveautes"];
            }
            return $results;
        } else {

            $query = "SELECT e.code as entreprise, src.code as code, a.valeur as assortiment, tf.code as tarification, ens.code as enseigne,
               count( p5 ) AS general , count( p2 ) AS nouveautes, count( p3 ) AS avion, count( p4 ) AS bateau
                FROM SogedialSiteBundle:Produit p

                INNER JOIN SogedialSiteBundle:Assortiment a WITH a.produit = p
                AND p.entreprise = a.entreprise

                LEFT JOIN SogedialSiteBundle:Tarif t1 WITH t1.produit = p
                AND p.entreprise = t1.entreprise AND t1.tarification IS NOT NULL AND t1.produit IS NOT NULL

                LEFT JOIN SogedialSiteBundle:Tarif t2 WITH t2.produit = p
                AND p.entreprise = t2.entreprise AND t2.enseigne IS NOT NULL AND t2.produit IS NOT NULL

                LEFT JOIN SogedialSiteBundle:Tarification tf WITH t1.tarification = tf
                LEFT JOIN SogedialSiteBundle:Enseigne ens WITH t2.enseigne = ens

                LEFT JOIN SogedialSiteBundle:Produit p2 WITH p2 = p
                AND p2.natureCode = 'NOUVEAUTE'

                LEFT JOIN SogedialSiteBundle:Produit p3 WITH p3 = p
                AND p3.preCommande = 1
                AND p3.marketingCode LIKE 'AVION%'

                LEFT JOIN SogedialSiteBundle:Produit p4 WITH p4 = p
                AND p4.preCommande = 1
                AND p4.marketingCode NOT LIKE 'AVION%'

                LEFT JOIN SogedialSiteBundle:Produit p5 WITH p5 = p
                AND p5.preCommande = 0

                INNER JOIN SogedialSiteBundle:$targetTable src WITH p.$target = src
                INNER JOIN SogedialSiteBundle:Entreprise e WITH a.entreprise = e
                
                INNER JOIN SogedialSiteBundle:Stock s WITH s.produit=p

                WHERE p.actif = 1 
                GROUP BY a.valeur, tf.code, ens.code, p.$target
                ";

            $em = $this->getContainer()->get('doctrine')->getManager();
            $em->createQueryBuilder();
            $results = $em->createQuery($query)->getScalarResult();

            return $results;
        }
    }

    private function setProduitCompteur($id, $objetType, $counter)
    {
        $produitCompteur = new ProduitCompteur();
    
        $produitCompteur->setId($id);
        $produitCompteur->setValeur($counter["assortiment"]);
        if($counter["tarification"] !== NULL){
            $produitCompteur->setTarif($counter["tarification"]);
        } else {
            $produitCompteur->setTarif($counter["enseigne"]);            
        }
        $produitCompteur->setEntreprise($this->getEntrepriseObjectByCode($counter["entreprise"]));
        $produitCompteur->setObjetType($objetType);
        $produitCompteur->setObjet($counter["code"]);
        $produitCompteur->setNouveautesCompteur($counter["nouveautes"]);
        $produitCompteur->setGeneralCompteur($counter["general"]);
        $produitCompteur->setPreCommandeAvion($counter["avion"]);
        $produitCompteur->setPreCommandeBateau($counter["bateau"]);

        return $produitCompteur;
    }

    protected function setPromotionCompteur(InputInterface $input, OutputInterface $output)
    {
        return parent::$em->getConnection()->query("update client c1 set c1.promotions_compteur=0;
        update client c1, (SELECT c.code_client, count(*) nb
        FROM promotion pt, produit p , `client` c, assortiment a
        where p.code_produit=pt.code_produit
        and (pt.code_client=c.code_client or pt.code_enseigne=c.code_enseigne)
        and pt.code_entreprise=c.code_entreprise
        and c.code_assortiment=a.code_assortiment
        and p.actif=true
        and pt.date_debut_validite <= NOW()
        and pt.date_fin_validite >= NOW()
        and pt.code_type_promo !='TX'
        group by c.code_client) c2
        set c1.promotions_compteur=c2.nb
        where c2.code_client=c1.code_client;");
    }

    protected function postCount(){
        $query = "update produits_compteur pc, 
        (SELECT code_famille, count(*) nb FROM `produit` WHERE `code_produit` LIKE '401-%' and actif=1 group by code_famille) p
        set pc.general_compteur=p.nb
        where p.code_famille = pc.objet and p.nb!=pc.general_compteur;";
        parent::$em->getConnection()->query($query);
        $query = "select pc.id, p.code_famille, p.nbn, pc.`nouveautes_compteur` from produits_compteur pc, 
        (SELECT code_famille, count(*) nbn FROM `produit` WHERE `code_produit` LIKE '401-%' and actif=1
         and nature_code='NOUVEAUTE' 
        group by code_famille) p
        where p.code_famille = pc.objet and p.nbn!=pc.`nouveautes_compteur`;";
        parent::$em->getConnection()->query($query);
        $query = "update produits_compteur pc, 
        (SELECT code_famille, count(*) nbn FROM `produit` WHERE `code_produit` LIKE '401-%' 
        and actif=1 and nature_code='NOUVEAUTE' group by code_famille) p
        set pc.nouveautes_compteur=p.nbn
        where p.code_famille = pc.objet and p.nbn!=pc.nouveautes_compteur;";
        $query = "SELECT objet, count(*) nb FROM `produits_compteur` where code_entreprise=401 and type_objet=2 and code_tarification = '4-T8' group by objet ORDER BY `nb` ASC;";
        parent::$em->getConnection()->query($query);        
    }

    protected function setProduitsCompteurs(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $qb = $em->createQueryBuilder();
        $qb->delete('SogedialSiteBundle:ProduitCompteur', 'pc');
        $result = $qb->getQuery()->execute();

        $searchService = $this->getContainer()->get('sogedial.recherche');

        $i = 0;

        $output->writeln('Rayons ...');
        $rayons = $this->query('r');
        $output->writeln('Familles ...');
        $familles = $this->query('f');
        $output->writeln('Secteurs ...');
        $secteurs = $this->query('s');
        $output->writeln('Catalogues ...');
        $catalogues = $this->query('g');

        $size = count($catalogues) + count($familles) + count($rayons) + count($secteurs);
        $batchSize = 20;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach($rayons as $rayon){
            if($rayon["enseigne"] === NULL && $rayon["tarification"] === NULL){
                continue;
            }
            $produitCompteur = $this->setProduitCompteur($i, 3, $rayon);

            $em->persist($produitCompteur);
            if (($i % $batchSize) === 0) {
                $progress->advance($batchSize);
                $now = new \DateTime();
                $output->writeln(' of entreprise imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        foreach($familles as $famille){
            if($famille["enseigne"] === NULL && $famille["tarification"] === NULL){
                continue;
            }
            $produitCompteur = $this->setProduitCompteur($i, 2, $famille);

            $em->persist($produitCompteur);

            if (($i % $batchSize) === 0) {
                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of entreprise imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        foreach($secteurs as $secteur){
            if($secteur["enseigne"] === NULL && $secteur["tarification"] === NULL){
                continue;
            }

            $produitCompteur = $this->setProduitCompteur($i, 1, $secteur);

            $em->persist($produitCompteur);
            if (($i % $batchSize) === 0) {
                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of entreprise imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        foreach($catalogues as $catalogue){
            if($catalogue["enseigne"] === NULL && $catalogue["tarification"] === NULL){
                continue;
            }            
            $produitCompteur = $this->setProduitCompteur($i, 0, $catalogue);

            $em->persist($produitCompteur);

            if (($i % $batchSize) === 0) {
                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of entreprise imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        $this->postCount();

        $em->flush();
        $em->clear();
        $progress->finish();
        $output->writeln('');

    }
}
