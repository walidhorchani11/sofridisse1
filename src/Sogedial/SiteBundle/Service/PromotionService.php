<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class PromotionService extends AbstractService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
    * @var ProductService
    */
    private $ps;

    /**
    * @var SimpleMySQLService
    */
    private $sql;

    public function __construct(EntityManager $em, SimpleMySQLService $sql, ProductService $ps)
    {
        $this->em = $em;
        $this->ps = $ps;
        $this->sql = $sql;
    }

    // cette fonction récupère l'ensemble des promotions applicables à un client donné (le client courant selon userInfoService)
    // on devrait l'appeler à l'extérieur de la boucle sur les produits pour tout type d'affichage (catalogue, commandes...)
    //
    // ce résultat est compatible avec getActualProductPriceAndStock()
    //
    // (Note historique : elle remplace getClientPromotionProductByCodeClient, getClientPromotionProductByCodeEnseigne et getPromotionClient)
    //
    // ATTENTION ! Il s'agit des promotions applicables. C'est une condition nécessaire, mais pas suffisante.
    // Le résultat de cette fonction ne doit pas être utilisé pour afficher la liste des promotions pour un client donné. En effet, il faut
    // le croiser avec les produits **affichables** (contraintes d'assortiment, stock, tarif...).
    //
    public function getUnitedPromos($code_enseigne = false, $code_client= false)
    {
        //Fix pour utliser cette fonction côté admin et unifier le tout
        $queryPromosElements = $this->ps->getPromosQueryElements($code_enseigne, $code_client);

        // ORDER BY code_produit est important pour pouvoir filtrer les résultats en un pass
        // ORDER BY code_type_promo est important car on va choisir le prix correspondant dans cet ordre-là.

        $queryPromos = 'SELECT '.$queryPromosElements['fields'].
            ' FROM promotion pr'.
            ' WHERE '.$queryPromosElements['condition'].
            ' ORDER BY code_produit ASC, code_type_promo ASC';

        $rows = $this->sql->query($queryPromos);

        // à ce stade, le résultat est un tableau 2D contenant une entrée de type "promo" par ligne (les clefs sont numériques)
        // [0] => [code_produit => 00010, code_type_promo => 26EN, ...]
        // [1] => [code_produit => 00012, code_type_promo => 21EF, ...]
        // [2] => [code_produit => 00012, code_type_promo => 41TX, ...]
        // [3] => [code_produit => 00015, ...]
        // ...

        // on souhaite le convertir en tableau avec UNE promo gagnante par produit, et l'indexer par le code produit
        // [00010] => [code_produit => 00010, code_type_promo => 26EN, ...]
        // [00012] => [code_produit => 00012, code_type_promo => 21EF, ...]
        // [00015] => ...

        // lors de calcul de prix pour chaque produit, cette structure sera la plus rapide

        $out=array();
        $code_produit = "NOT INIT";                             // n'utilisez pas null ; si il y a des nulls dans les code_produit dans la base (invalide!), cela les masquerait => difficile à debugger

        // parcours dans l'ordre de sortie de la requête SQL
        foreach ($rows as $row) {
            if ($row['code_produit'] !== $code_produit) {       // pour les code_produit's qui se répètent, on ne prend que le premier
                $code_produit = $row['code_produit'];
                $out[$code_produit] = $row;                     // ne supprimez pas code_produit dans la valeur, l'avoir pourrait être utile pour le debug
            }
        }

        return $out;
    }

    /**
    * @return [
    *    <SogedialSiteBundle:Client> => [
    *        <SogedialSiteBundle:Promotion>
    *        <SogedialSiteBundle:Promotion>
    *        <SogedialSiteBundle:Promotion>
    *        ...
    *    ]
    *    <SogedialSiteBundle:Client> => [
    *        ...
    *    ]
    *    ...
    *]
    */
    public function getStockEngagementDemandeEmailByClients()
    {
        $clients = array();
        $promotions = $this->em->getRepository('SogedialSiteBundle:Promotion')->getPromotionsWithStockEngagementDemandOnActifsClients();

        foreach($promotions as $promotion){
            $clientCode = $promotion->getClient()->getCode();

            if(!array_key_exists($clientCode, $clients)){
                $clients[$clientCode] = array();
            } 

            array_push($clients[$clientCode], $promotion);
        }

        return $clients;
    }

    /*
    public function appendPromotionCodeToProducts(array $products, $code_enseigne = false, $code_client= false)
    {
        $productsWithPromotions = $this->getUnitedPromos($code_enseigne, $code_client);

        foreach($products as $key => $product){
            if(array_key_exists("code", $product) && array_key_exists($product["code"], $productsWithPromotions)){
                $products[$key]["code_promotions"] = $productsWithPromotions[$product["code"]]["code_promotion"];
            } else {
                $products[$key]["code_promotions"] = NULL;
            }
        }

        return $products;
    }
    */

}
