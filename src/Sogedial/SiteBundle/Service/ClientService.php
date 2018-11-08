<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;

class ClientService
{

    private $ms;
    private $em;
    private $sql;
    private $ui;

    public function __construct(MultiSiteService $ms, EntityManager $em, SimpleMySQLService $sql, UserInfo $ui)
    {
        $this->ms = $ms;
        $this->em = $em;
        $this->sql = $sql;
        $this->ui = $ui;
    }

    public function getEm(){
        return $this->em;
    }

    // cette fonction centralise les conditions qui définissent les promotions applicables
    public function getPromosQueryElements()
    {
        $today = new \DateTime('now');
        $todayString = '"'. $today->format('Y-m-d') .'"';
        $codeClientString = '"'. $this->ui->code_client .'"';
        $codeEnseigneString = '"'. $this->ui->code_enseigne .'"';

        // Les promotions ont 3 types de propriétés
        // 1) Le filtrage statique : ne dépendent pas de produit
        //    code_enseigne
        //    code_client
        //    les dates
        //    regroupement_client ?
        //    code_category_client ?
        // 2) Le filtrage dynamique : dépend du produit => doivent être inclus dans les résultats de cette fonction
        //    code_produit
        //    code_supplier ??
        // 3) autres (propriétés)
        //    prix_ht
        //    stock_engagement_*
        //    revente_perte
        //    ...
        //    nous incluons aussi la date de DEBUT dans les résultats pour le tri des résultats dans le dashboard
        //    nous incluons aussi la date de FIN dans les résultats car ce sont potentiellement des informations intéressantes pour le client,
        //       en plus d'être un critère de filtrage
        //    nous incluons aussi code_type_promo (mappé) pour les besoins de debug. Ce champ ne devrait pas être utilisé par le code qui traite
        //       ces résultats en aval.

        // remarque : pas besoin de filtre sur code_type_promo != '', car l'ingestion s'en occupe ; en revanche, on enlève les promos inconnues
        // et on filtre dans l'ordre des priorités des promos

        // TODO il serait mieux de le faire en ingestion --> à faire dès que toutes les promos passent par ici

        // L'ordre relatif des priorités des promos est défini ici.
        // Les promos écrasent le traitement d'exception.
        $codeTypePromoMapper = "CASE pr.code_type_promo".
            " WHEN 'EF' THEN '21EF'".               // engagement
            " WHEN 'MA' THEN '22MA'".               // marché
            " WHEN 'CE' THEN '23CE'".               // ??
            " WHEN 'CL' THEN '24CL'".               // client
            " WHEN 'RG' THEN '25RG'".               // regroupement de clients
            " WHEN 'EN' THEN '26EN'".               // enseigne
            " WHEN 'CA' THEN '27CA'".               // catégorie de clients
            " WHEN 'FR' THEN '28FR'".               // fournisseur
            " WHEN 'TX' THEN '41TX'".               // traitement d'exception : codeTraitementException est déjà mappé sur codeTypePromo=TX
            " ELSE '99XX'".                         // on filtre les promos inconnues
            " END as code_type_promo";

        // deux filtres :
        // 1 = par date
        // 2 = soit le client, soit l'enseigne doit matcher
        // 3 = TODO futur : ' OR pr.regroupement_client = '.$regroupementClientString.' OR pr.code_category_client = '.$codeCategoryClientString

        $queryPromosElements = array();

        $queryPromosElements['fields'] = 'pr.code_promotion, pr.date_debut_validite, pr.date_fin_validite, pr.prix_ht, pr.code_produit, pr.code_supplier, pr.revente_perte, pr.stock_engagement, pr.stock_engagement_restant, pr.demande_en_cours, pr.commande_facture, '.
            $codeTypePromoMapper;
        $queryPromosElements['condition'] = 'code_type_promo!="99XX"'.
            ' AND pr.date_debut_validite <= '.$todayString.
            ' AND pr.date_fin_validite >= '.$todayString.
            ' AND (pr.code_enseigne = '.$codeEnseigneString.' OR pr.code_client = '.$codeClientString.')';

        return $queryPromosElements;
    }

    // cette fonction récupère l'ensemble des promotions applicables à un client donné (le client courant selon userInfoService)
    // on devrait l'appeler à l'extérieur de la boucle sur les produits pour tout type d'affichage (catalogue, commandes...)
    //
    // ce résultat est compatible avec getActualProductPriceAndStock()
    //
    // (Note historique : elle remplace getClientPromotionProductByCodeClient, getClientPromotionProductByCodeEnseigne et getPromotionClient)
    //
    // ATTENTTION ! Il s'agit des promotions applicables. C'est une condition nécessaire, mais pas suffisante.
    // Le résultat de cette fonction ne doit pas être utilisé pour afficher la liste des promotions pour un client donné. En effet, il faut
    // le croiser avec les produits **affichables** (contraintes d'assortiment, stock, tarif...).
    //
    public function getUnitedPromos()
    {
        $queryPromosElements = $this->getPromosQueryElements();

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

    //
    // retourne un tableau contenant de 0 à 5 paliers de prix, trié dans l'ordre croissant,
    // les paliers sont uniques
    //
    // exemples :
    // array()                      -- pas de prix trouvé
    // array(1=>9.99)               -- le prix unique de 9.99, pas de volume d'achat min
    // array(3=>5.0, 5=>7.0)        -- deux paliers avec le volume d'achat min = 3
    //

    // cette fonction utilise les informations du client configurées via setEnseigneAssortiment()
//     public function getActualProductPriceAndStock ($product,
// //        $productsByClientInPromotion, $productsByEnseInPromotion, $promos,
//         $unitedPromos)
//     {
//         $result = array();

//         $pcb = $product->getPcb();
//         // pour éviter le risque de division par zéro. Certes, ce sont des données invalides...
//         // TODO bloquer niveau ingestion une fois qu'on a compris comment gérer les pcb non-entiers.
//         if ($pcb == 0) {
//             $pcb = 1;
//         }

//         $codeProduit = $product->getCode();

//         $stock = $product->getStock()->getStockTheoriqueColis();
//         if ($this->ms->hasFeature('vente-par-unite')) {                     // TODO lors de l'unification, cela devrait devenir une propriété de produit, pas de société
//             if ($stock === "") {
//                 $stock = $product->getStock()->getStockTheoriqueUc();
//             } else {
//                 $stock = $stock * $pcb;
//             }
//         }
//         $result['stock'] = $stock;                                          // cette valeur est écrasée dans certaines situations (promo EF, ...)

//         $result['priceArray'] = array(1 => "9999.99");                      // la valeur par défaut ne devrait jamais être affichée
//         $result['priceType'] = '98ND';                                      // non disponible

//         // priorité 1 : existe-t-il une promo (client, enseigne, autre...) ou un tarif d'exception (promo "TX") ?
//         $result['isPromo'] = false;
//         $promo = null;
//         if (isset($unitedPromos[$codeProduit])) {
//             $promo = $unitedPromos[$codeProduit];
//         }

//         if (isset($promo)) {

//             $result['priceArray'] = array( 1=> $promo['prix_ht'] );         // 1 = quantité minimale (pseudo-palier)
//             if ($promo['code_type_promo'] !== '41TX') {                     // tarif d'exception n'est PAS une promo !
//                 $result['isPromo'] = true;
//             }
//             $result['priceType'] = $promo['code_type_promo'];

//             if ($promo['code_type_promo'] === '21EF') {
//                 $result['promotionCommandeEnCours'] = intval(intval($promo['demande_en_cours'])/$pcb);
//                 $result['promotionCommandeFacture'] = intval(intval($promo['commande_facture'])/$pcb);
//                 $result['stockInit'] = intval($promo['stock_engagement']);                                  // division se fait dans le dialog d'info. A changer ?...
//                 $result['stock'] = intval(intval($promo['stock_engagement_restant']) / $pcb);
//                 $result['EF'] = $promo["code_promotion"]; //on vérifie juste la présence de ce flag dans product-form.html.twig et permet d'identifier la promotion d'un produit
//             }
//         } else {

//             // priorité 2 : tarif de la table "tarif" (soit par enseigne, soit par tarification)

//             $tarifs=$product->getTarifs();
//             $code_tarification = $this->ui->code_tarification;
//             $code_enseigne = $this->ui->code_enseigne;
//             $actualPrice = null;

//             if($this->ms->hasFeature('tarifs-tarification')){
//                 foreach ($tarifs as $tarif) {
//                     if ($tarif->getTarification()->getCode() === $code_tarification) {    // dans ce cas, les tarifs *doivent* avoir une tarification
//                         $actualPrice = $tarif->getPrixHt();
//                         break;
//                     }
//                 }
//             } else if($this->ms->hasFeature('tarifs-enseigne')){
//                 foreach ($tarifs as $tarif) {
//                     if ($tarif->getEnseigne()->getCode() === $code_enseigne) {            // dans ce cas, les tarifs *doivent* avoir un code enseigne
//                         $actualPrice = $tarif->getPrixHt();
//                         break;
//                     }
//                 }
//             } else if($this->ms->hasFeature('tarifs-marge')){
//                 // foreach ($tarifs as $tarif) {
//                 //     if ($tarif->getEnseigne()->getCode() === $code_enseigne) {            // dans ce cas, les tarifs *doivent* avoir un code enseigne
//                 //         $actualPrice = $tarif->getPrixHt();
//                 //         break;
//                 //     }
//                 // }
//                 var_dump($tarifs); die();
//             }

//             if ($actualPrice !== null) {
//                 $result['priceType'] = '81TS';
//                 $result['priceArray'] = array(1=>$actualPrice);
//             } else {

//                 // priorité 3 (la plus basse) : le prix degressif

//                 $decreasingPrices = $this->em->getRepository('SogedialSiteBundle:Degressif')
//                     ->findBy(array('produit' =>$product), array('palier' => 'ASC'));

//                 // on trie le tableau de prix degressifs en se basant sur le palier
//                 // dont la valeur indique la quantité minimale d'un palier
//                 // ainsi, on fournit le tableau de prix déjà trié

//                 $actualPrice = array();
//                 $previousStep = 0;
//                 foreach ($decreasingPrices as $decreasingPrice) {
//                     $step = $decreasingPrice->getPalier();
//                     if ($step === $previousStep) {
//                         // on s'arrête dès que les paliers commencent à se répéter
//                         break;
//                     }
//                     $actualPrice[$step] = $decreasingPrice->getPrixHt();
//                     $previousStep = $step;
//                 }

//                 if ($previousStep !== 0) {                      // on a trouvé le tarif degressif
//                     $result['priceType'] = '82TD';
//                     $result['priceArray'] = $actualPrice;
//                 }
//                 // Sinon, il nous reste la valeur par défaut (98ND / 9999.99). Cela ne devrait jamais arriver.
//             }
//         }

        // $isPromo = false;
        // if ($this->ms->getSociete()==="301")   // Sofrigu
        // {
        //     // TODO factoriser la logique Sofrigu avec le reste

        //     $client = $this->em->getRepository('SogedialSiteBundle:Client')
        //         ->findOneBy(array('code' => $this->ui->code_client));

        //     // priorité 1 : promotion client (promotion spécifique)
        //     $promo = $this->em->getRepository('SogedialSiteBundle:Promotion')
        //                     ->findOneBy(array('produit' =>$product, 'client' => $client));
        //     if ($promo) {
        //         $actualPrice = array(1 => $promo->getPrixHt());
        //         $isPromo = true;
        //     }
        //     else
        //     {
        //         // priorité 2 : promotion enseigne (promotion générale)
        //         $promoEnseigne = $this->em->getRepository('SogedialSiteBundle:Promotion')
        //                     ->findOneBy(array('produit' =>$product, 'enseigne' => $client->getEnseigne()));
        //         if ($promoEnseigne) {
        //             $actualPrice = array(1 => $promoEnseigne->getPrixHt());
        //             $isPromo = true;
        //         }
        //         else {
        //             // priorité 3 : un prix spécifique pour ce client (table "tarif")
        //             $specificPrice = $this->em->getRepository('SogedialSiteBundle:Tarif')
        //                     ->findOneBy(array('produit' =>$product, 'enseigne' => $client->getEnseigne()));

        //             if ($specificPrice ) {
        //                 $actualPrice = array(1 => $specificPrice->getPrixHt());
        //             }else{
        //                 // priorité 4 (la plus basse) : le prix degressif
        //                 $decreasingPrices = $this->em->getRepository('SogedialSiteBundle:Degressif')
        //                     ->findBy(array('produit' =>$product), array('palier' => 'ASC'));

        //                 // on trie le tableau de prix degressifs en se basant sur le palier
        //                 // dont la valeur indique la quantité minimale d'un palier
        //                 // ainsi, on fournit le tableau de prix déjà trié

        //                 $actualPrice = array();
        //                 $previousStep = 0;
        //                 foreach ($decreasingPrices as $decreasingPrice) {
        //                     $step = $decreasingPrice->getPalier();
        //                     if ($step === $previousStep) {
        //                         // on s'arrête dès que les paliers commencent à se répéter
        //                         break;
        //                     }
        //                     $actualPrice[$step] = $decreasingPrice->getPrixHt();
        //                     $previousStep = $step;
        //                 }
        //             }
        //         }
        //     }

        //     $result['stock'] = $product->getStock()->getStockTheoriqueColis();      // TODO TODO penser au futur (commandes en colis ET en unités)
        //     if ($result['stock'] === "")
        //     {
        //         $result['stock'] = $product->getStock()->getStockTheoriqueUc();
        //     }
        //     else
        //     {
        //         $result['stock'] = $result['stock'] * $pcb;
        //     }
        // }
        // else
        // {
        //     // BUG : les règles ne sont pas dans le bon ordre par rapport aux spécifications

        //     $result['stock'] = $product->getStock()->getStockTheoriqueColis();

        //     $actualPrice = 0;       // ne devrait jamais arriver jusqu'à l'affichage

        //     $tarifs=$product->getTarifs();
        //     foreach ($tarifs as $tarif)
        //     {
        //         if($this->ms->hasFeature('tarifs-tarification')){
        //             if ($tarif->getTarification()->getCode() === $this->ui->code_tarification)     // dans ce cas, les tarifs *doivent* avoir une tarification
        //             {
        //                 $actualPrice = $tarif->getPrixHt();
        //                 break;
        //             }
        //         }
        //         else{
        //             if ($tarif->getEnseigne()->getCode() === $this->ui->code_enseigne)             // dans ce cas, les tarifs *doivent* avoir un code enseigne
        //             {
        //                 $actualPrice = $tarif->getPrixHt();
        //                 break;
        //             }
        //         }
        //     }

        //     // Règle N°1 : Promo client
        //     if (count($productsByClientInPromotion) > 0 && in_array($this->ui->code_client, $promos['promoClient'])) {
        //         foreach ($productsByClientInPromotion as $productByClientInPromotion) {
        //             if ($productByClientInPromotion['codeProduit'] == $product->getCode())
        //             {
        //                 if (in_array($productByClientInPromotion['codeTypePromo'],  array('EF','MA','CE','CL','RG','EN','CA')  ))
        //                 {
        //                     $actualPrice = $productByClientInPromotion['prixHt'];
        //                     $isPromo = true;
        //                 }
        //                 if ($productByClientInPromotion['codeTypePromo'] == 'EF')
        //                 {
        //                     $result['promotionCommandeEnCours'] = intval(intval($productByClientInPromotion['commandeEnCours'])/$pcb);
        //                     $result['promotionCommandeFacture'] = intval(intval($productByClientInPromotion['commandeFacture'])/$pcb);
        //                     $result['stockInit'] = intval($productByClientInPromotion['stockEngagement']);      // division se fait dans le dialog d'info. A changer ?...
        //                     $result['stock'] = intval(intval($productByClientInPromotion['stockEngagementRestant']) / $pcb);
        //                     $result['EF'] = $productByClientInPromotion["code"];
        //                 }
        //             }
        //         }
        //     }

        //     // Règle N°2 : Promo enseigne
        //     if (count($productsByEnseInPromotion) > 0 && in_array($this->ui->code_enseigne, $promos['promoEnseigne'])) {
        //         foreach ($productsByEnseInPromotion as $productByEnseInPromotion) {
        //             if ($productByEnseInPromotion['codeProduit'] == $product->getCode())
        //             {
        //                 if (in_array($productByEnseInPromotion['codeTypePromo'],  array('EF','MA','CE','CL','RG','EN','CA')  ))
        //                 {
        //                     $actualPrice = $productByEnseInPromotion['prixHt'];
        //                     $isPromo = true;
        //                 }
        //                 if ($productByEnseInPromotion['codeTypePromo'] == 'EF')
        //                 {
        //                     $result['promotionCommandeEnCours'] = intval(intval($productByEnseInPromotion['commandeEnCours'])/$pcb);
        //                     $result['promotionCommandeFacture'] = intval(intval($productByEnseInPromotion['commandeFacture'])/$pcb);
        //                     $result['stockInit'] = intval($productByEnseInPromotion['stockEngagement']);      // division se fait dans le dialog d'info. A changer ?...
        //                     $result['stock'] = intval(intval($productByEnseInPromotion['stockEngagementRestant']) / $pcb);
        //                     $result['EF'] = $productByEnseInPromotion["code"];

        //                 }
        //             }
        //         }
        //     }

        //     // Règle N°3 : Exception
        //     $listEnseigneByProduit = $this->em->getRepository('SogedialSiteBundle:Produit')->getListCodeEnseigneByCodeProduit($product->getCode(), $this->ui->code_client, $this->ui->code_enseigne);
        //     $checkException = $this->em->getRepository('SogedialSiteBundle:Produit')->getIfProductHasCodeExceptionByCodeProduitAndCodeEnseigne($product->getCode(), $this->ui->code_enseigne);

        //     if ($listEnseigneByProduit == true && $checkException == true) {
        //         $actualPrice = $this->em->getRepository('SogedialSiteBundle:Produit')->getTarifException($this->ui->code_enseigne, $product->getCode());
        //     }

        //     // bien qu'il n'y a pas de paliers ici (tarifs degressifs), on imite un unique palier afin de simplifier les fonctions qui suivent
        //     $actualPrice = array(1=>$actualPrice);

        // }


        // $result['priceArray'] = $actualPrice;
        // $result['isPromo'] = $isPromo;

    //     return $result;
    // }

    public function getDegressivePrice($qty, $priceArray)
    {
        $selected_price = 0;
        foreach ($priceArray as $qty_min => $price)
        {
            if ($qty_min > $qty)
            {
                break;
            }
            $selected_price = $price;           // les paliers doivent être triés en croissant ; on continue tant qu'on trouve un qui convient
        }
        return $selected_price;
    }

    // cette fonction est colissage-agnostique - elle peut opérer sur les colis, sur les unités, sur les peaux de lions...
    // $priceArray = [ palier_min => palier_prix, ... ]

    // L'équivalent JavaScript : script.js:getLineItemTotal()

    public function getLineItemTotal($qty, $pcb, $priceArray)
    {

        $selected_price = $this->getDegressivePrice($qty, $priceArray);

        $result = $selected_price * $qty;          // zéro possible si quantité < quantité minimale requise (le début du premier palier)

        if(!($this->ms->hasFeature('vente-par-unite'))) {
            // ici, "quantité" dans le panier = colis => besoin de multiplier par pcb (avec ou sans tarif degressif)
            $result = $result * $pcb;
        }

        return $result;
    }

    private function getProductDetailFields()
    {
        return array(
        "fields" => "p.denomination_produit_base as text, p.code_produit as code, p.ean13_produit as ean13, m.libelle as marque",
        "joins" => "LEFT JOIN marque m ON m.code_marque=p.code_marque"      // joins necessary for obtaining the fields above
        );
    }

    // Pour un client avec les codes enseigne, tarification et assortiment (valeur) donnés, retourne une requête MySQL
    // qu'il faut utiliser pour obtenir l'ensemble des produits affichables pour ce client.
    //
    // Cette fonction prend en compte l'assortiment, le stock, le tarif et des règles spécifiques par société

    // $from doit produire un surensemble de la table "produit" avec alias "p" ; exemples :
    //   "produit p"
    //   "table1 JOIN produit p ON p.smth=table1.else"

    // $where peut contenir une condition supplémentaire. Cela peut aussi être une chaîne vide.

    // Remarque : il faut laisser enseigne, tarification et assortiment en paramètre pour qu'un admin puisse manipuler plusieurs clients différents
    public function getEnrichQuery($code_enseigne, $code_tarification, $code_assortiment, $from, $where, $extraFields)
    {

        // enrichissements possibles :
        // la valeur de tarif
        // rayon, secteur
        // tarif degressif qui va bien

        $productDetailFields=$this->getProductDetailFields();

        return "SELECT ".($productDetailFields['fields']).(($extraFields==="")?"":(", ".$extraFields))."
            FROM ".$from."
            ".($productDetailFields['joins'])."
            LEFT JOIN famille f ON f.code_famille = p.code_famille
            INNER JOIN assortiment ass ON ass.code_produit=p.code_produit
            LEFT JOIN tarif t ON t.code_produit=p.code_produit".
            (($this->ms->hasFeature('tarifs-tarification')) ? " AND t.code_tarification = '".$code_tarification."'" : "  AND t.code_enseigne = '".$code_enseigne."' " ).
            "INNER JOIN stock s ON s.code_produit=p.code_produit
            WHERE ass.valeur='".$code_assortiment."'
            AND p.actif = 1 ".(($where==="")?"":("AND (".$where.")")).
            (($this->ms->hasFeature('tarifs-degressifs')) ? "" : " AND t.code_produit IS NOT NULL " );
        // cela devient un INNER JOIN si 'tarifs-degressifs' est absent

        // notez que la condition sur t.code_enseigne n'est pas dans WHERE mais dans LEFT JOIN. Cela permet de ne joindre que
        // les tarifs qui matchent l'enseigne, mais laisser passer le produit si il n'y a pas de tarif correspondant

        // un tel produit sera enlevé par la dernière condition (optionnelle) sur t.code_produit
    }

    public function getSingleProduct($code)
    {
        $query = $this->getEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment, "produit p", 'p.code_produit="'.$code.'"', "");
        
        $productBasicInfo = $this->sql->query($query);

        return $productBasicInfo;
    }

    public function getNewProductsAndPromosForDashboard()
    {
        $queryNew = $this->getEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment, "produit p", "p.nature_code='NOUVEAUTE'", "");
        $queryNew .= " ORDER BY p.updated_at DESC";
        $queryNew .= " LIMIT 0,2";

        $rowsNew = $this->sql->query($queryNew);

        $queryPromosElements = $this->getPromosQueryElements();
        // $productDetailFields=$this->getProductDetailFields();

        // $queryPromos = 'SELECT '.$queryPromosElements['fields'].', '.($productDetailFields['fields']).
        //     ' FROM promotion pr'.
        //     ' INNER JOIN produit p ON p.code_produit=pr.code_produit'.          // FROM promotion JOIN produit est peut-être plus rapide que l'inverse
        //     ' '.($productDetailFields['joins']).
        //     ' WHERE '.$queryPromosElements['condition'].
        //     ' ORDER BY pr.code_produit ASC, code_type_promo ASC';

        $queryPromos = $this->getEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment,
            "promotion pr INNER JOIN produit p ON p.code_produit=pr.code_produit",
            ($queryPromosElements['condition']).' AND pr.code_type_promo != "TX"',
            $queryPromosElements['fields']);
        $queryPromos .= ' ORDER BY pr.code_produit ASC, code_type_promo ASC';

        $rowsPromos = $this->sql->query($queryPromos);

        // enlever les promos en double
        // + tri (équivalent de " ORDER BY pr.date_debut_validite DESC", mais malheureusement, on ne peut pas le faire en SQL)
        // + équivalent de "LIMIT 0,2"
        $out=array();
        $out_count = 0;
        $code_produit = "NOT INIT";                             // n'utilisez pas null ; si il y a des nulls dans les code_produit dans la base (invalide!), cela les masquerait => difficile à debugger

        // parcours dans l'ordre de sortie de la requête SQL == l'ordre de priorités des promos
        $max_date_debut = "1900-01-01 00:00:01";
        $max2_date_debut = "1900-01-01 00:00:00";
        $max_idx = -1;
        $max2_idx = -1;

        foreach ($rowsPromos as $row) {
            if ($row['code_produit'] !== $code_produit) {       // pour les code_produit's qui se répètent, on ne prend que le premier
                $code_produit = $row['code_produit'];
                $out[] = $row;

                // on trouve les deux les plus récents
                if ($row['date_debut_validite'] > $max_date_debut)
                {
                    $max2_date_debut = $max_date_debut;
                    $max2_idx = $max_idx;
                    $max_date_debut = $row['date_debut_validite'];
                    $max_idx = $out_count;
                } elseif ($row['date_debut_validite'] > $max2_date_debut)
                {
                    $max2_date_debut = $row['date_debut_validite'];
                    $max2_idx = $out_count;
                }
                $out_count+=1;
            }
        }

        $outlimit2=array();
        if ($max_idx != -1) {
            $outlimit2[]=$out[$max_idx];
            if ($max2_idx != -1) {
                $outlimit2[]=$out[$max2_idx];
            }
        }

        $promonews = array_merge($rowsNew, $outlimit2);

        return $promonews;          // il faut appeler refactorAllProducts sur le résultat avant de le passer à une vue
    }
}
