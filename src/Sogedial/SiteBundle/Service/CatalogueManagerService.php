<?php

namespace Sogedial\SiteBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\LigneCommande;
use Sogedial\SiteBundle\Entity\Photo;
use Sogedial\SiteBundle\Entity\Promotion;
use Sogedial\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class CatalogueManagerService
{
    private function getEntrepriseCodeOrder(User $user)
    {
        /*
        if(!isset($this->em)){
            $this->em = $this->getDoctrine()->getManager();
        }
        $entrepriseCourante = $user->getEntrepriseCourante();
        if($user->getPreCommande() !== NULL) {
            return $this->em
                ->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $entrepriseCourante))
                ->getEntrepriseParent()->getCode();
        } else {
            return $entrepriseCourante;
        }
        */
        return $user->getEntrepriseCourante();
    }

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var EntityManager
     */
    private $em;

    private $ms;

    private $ps;
    private $rs;
    private $cs;
    private $colisService;

    public function __construct(TokenStorage $tokenStorage, EntityManager $em, MultiSiteService $ms, ProductService $ps, Recherche $rs, Container $cs, ColisService $colisService)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->ms = $ms;
        $this->ps = $ps;
        $this->rs = $rs;
        $this->cs = $cs;
        $this->colisService = $colisService;
    }

    public function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @return array
     */
    public function getCommande()
    {
        $result = array();
        $order = $this->em->getRepository('SogedialSiteBundle:Commande')->getCurrentOrderByUserNewVersion(
            $this->getUser()->getId(),
            $this->getEntrepriseCodeOrder($this->getUser())
        );

        $temperatures = array ('ambient' => 'SEC', 'positiveCold' => 'FRAIS', 'negativeCold' => 'SURGELE');                     // TODO global variable
        $totalAmount['totalAmount'] = 0;
        if (null !== $order['o_id']) {
            $produitRepo = $this->em->getRepository('SogedialSiteBundle:Produit');
            $totalAmount = $produitRepo->getOrderTotalAmount($order['o_id'], $this->ms->hasFeature('vente-par-unite'));
            foreach ($temperatures as $temperature => $temperatureProduit) {
                $order['counts'][$temperature] = $produitRepo->getOrderCount($order['o_id'],$temperatureProduit)['countResult'];
            }
            $totalVolumeWeight = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order['o_id']);
            $poidsTotal = $totalVolumeWeight["poidsTotal"];
            $volumeTotal = $totalVolumeWeight["volumeTotal"];
        } else {
            foreach ($temperatures as $temperature => $temperatureProduit) {
                $order['counts'][$temperature] = 0;
                $poidsTotal = 0;
                $volumeTotal = 0;
            }
        }

        // comptes de nombre de produits pour le basket - on enlève les totaux qui n'existent pas pour cette société
        if (!($this->ms->hasTemperature('ambient'))) {
            unset($order['counts']['ambient']);
        }
        if (!($this->ms->hasTemperature('positiveCold'))) {
            unset($order['counts']['positiveCold']);
        }
        if (!($this->ms->hasTemperature('negativeCold'))) {
            unset($order['counts']['negativeCold']);
        }

        $result['order'] = $order;
        $result['totalAmount'] = $totalAmount;

        if ($this->ms->hasTemperature('negativeCold')) {
            $negativeColdVolumeWeight = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeightByTemp($order['o_id'], 'negativeCold');
            $result['poidsNegativeCold']  = $negativeColdVolumeWeight["poidsTemp"] ? $negativeColdVolumeWeight["poidsTemp"] : '0';
            $result['volumeNegativeCold'] = $negativeColdVolumeWeight["volumeTemp"] ? $negativeColdVolumeWeight["volumeTemp"] : '0';
        }

        if ($this->ms->hasTemperature('positiveCold')) {
            $positiveColdVolumeWeight = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeightByTemp($order['o_id'], 'positiveCold');
            $result['poidsPositiveCold']  = $positiveColdVolumeWeight["poidsTemp"] ? $positiveColdVolumeWeight["poidsTemp"] : '0';
            $result['volumePositiveCold'] = $positiveColdVolumeWeight["volumeTemp"] ? $positiveColdVolumeWeight["volumeTemp"] : '0';
        }

        if ($this->ms->hasTemperature('ambient')) {
            $ambientVolumeWeight = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeightByTemp($order['o_id'], 'ambient');
            $result['poidsAmbient']  = $ambientVolumeWeight["poidsTemp"] ? $ambientVolumeWeight["poidsTemp"] : '0';
            $result['volumeAmbient'] = $ambientVolumeWeight["volumeTemp"] ? $ambientVolumeWeight["volumeTemp"] : '0';
        }

        $result['poidsTotal'] = $poidsTotal;
        $result['volumeTotal'] = $volumeTotal;

        return $result;
    }

    /**
     * @return array
     */
    public function getCommande2($id, $entreprise)
    {
        $result = array();
        $order = $this->em->getRepository('SogedialSiteBundle:Commande')
            ->getCurrentOrderByUserNewVersion($id,$entreprise);

        $totalAmount = null;
        if (null !== $order['o_id']) {
            $totalAmount = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalAmount($order['o_id']);
            $totalVolumeWeight = $this->em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order['o_id']);

            $result['poidsTotal'] = $totalVolumeWeight["poidsTotal"];
            $result['volumeTotal'] = $totalVolumeWeight["volumeTotal"];
        }

        $result['order'] = $order;
        $result['totalAmount'] = $totalAmount;

        return $result;
    }

    /**
     * @return mixed
     */
    public function getSidebarElement(User $user)
    {
        $entreprisePreCommande = null;
        if($user->getPreCommande() !== NULL){
            $entreprisePreCommande = $this->em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array("code" => $user->getEntrepriseCourante()));
        }
        $valeurAssortiment = null;
        $currentUser = $this->getUser();
        $entityClient = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $currentUser->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));

        $entityAssortiment = $this->ms->getAssortimentValeur($entityClient);//$entityClient->getAssortiment();

        if($this->ms->getRegion() === '3') {
            $valeurAssortiment = '777';
        } else {
            $valeurAssortiment = $entityAssortiment;
        }

        if($this->ms->hasFeature("tarifs-tarification")){
            if($entityClient->getTarification()){
                $codeTarification = $entityClient->getTarification()->getCode();
            } else {
                $codeTarification = $entityClient->getEnseigne()->getCode();
            }
        } else {
            $codeTarification = $entityClient->getEnseigne()->getCode();
        }


        if($currentUser->getPreCommande() === NULL){
            $entrepriseCode = $entityClient->getEntreprise()->getCode();
        } else {
            $entrepriseCode = $entityClient->getEntreprise()->getEntrepriseParent()->getCode();
        }


        $preCommandeMode = $this->getUser()->getPreCommande() !== NULL && $this->getUser()->getPreCommande();

        if($entrepriseCode && $entrepriseCode === '401') {
            $family = $this->em->getRepository('SogedialSiteBundle:Produit')->getHierarchicalForSogedial($valeurAssortiment, $codeTarification, $entrepriseCode, $preCommandeMode, $this->ms->hasFeature('tarifs-degressifs'));
        } else {
            $family = $this->em->getRepository('SogedialSiteBundle:Produit')->getHierarchical($valeurAssortiment, $codeTarification, $entrepriseCode, $preCommandeMode, $this->ms->hasFeature('tarifs-degressifs'));
        }

        $nouveautes = 0;
        $keys = ['secteur', 'rayon', 'famille'];

        $catalogue = 0;

        $test = function ($level, $keys, $item) use (&$test) {
            if (!$key = array_shift($keys)) {
                return [];
            }
            $id = $item[$key];
            if (empty($level[$id])) {
                $level[$id] = [
                    'id' => $id,
                    'class' => self::slug($item[$key . '_fr']),
                    'fr' => $item[$key . '_fr'],
                    'children' => [],
                    'counter' => 0
                ];
            }
            $levelNode = &$level[$id];
            $levelNode['counter'] += $item["counter"];

            $levelNode['children'] = $test($levelNode['children'], $keys, $item);
            return $level;
        };

        $tree = array_reduce($family, function ($memo, $item) use (&$test, $keys) {
            return $test($memo, $keys, $item);
        }, []);

        foreach($tree as $node){
            $catalogue += $node["counter"];
        }

        $tree["catalogue"] = ["counter" => $catalogue];

        $nouveauteCount = $this->em->getRepository('SogedialSiteBundle:Produit')->getNouveauteCompteur($valeurAssortiment, $codeTarification, $entrepriseCode, $preCommandeMode, $this->ms->hasFeature('tarifs-degressifs'));

        $tree["nouveautes"] = ["counter" => intval($nouveauteCount['counter'])];

        $promotionCount = $this->em->getRepository('SogedialSiteBundle:Produit')->getPromotionCompteur($entityClient->getCode(), $entityClient->getEnseigne()->getCode(), $valeurAssortiment, $codeTarification, $entrepriseCode, $preCommandeMode, $this->ms->hasFeature('tarifs-degressifs'));

        $tree["promotions"] = ["counter" => intval($promotionCount['counter'])];

        return $tree;
    }

    public function getPromotionsCount() {
        return $this->getSidebarElement($this->getUser())["promotions"]["counter"];
    }

    public function getNouveautesCount() {
        return $this->getSidebarElement($this->getUser())["nouveautes"]["counter"];
    }

    /**
     * code mort
     * @return mixed
     */
    public function getSidebarElementToAdmin()
    {
        $valeurAssortiment = null;
        $currentUser = $this->getUser();
        $family = $this->em->getRepository('SogedialSiteBundle:Produit')->getHierarchical2('sec', null, "MODE_COMMERCIAL");

        $keys = ['f', 'sf', 'ssf'];

        $test = function ($level, $keys, $item) use (&$test) {
            if (!$key = array_shift($keys)) {
                return [];
            }
            $id = $item[$key];
            if (empty($level[$id])) {
                $level[$id] = [
                    'id' => $id,
                    'class' => self::slug($item[$key . '_fr']),
                    'fr' => $item[$key . '_fr'],
                    'children' => [],
                    'counter' => 0];
            }
            $levelNode = &$level[$id];
            $levelNode['counter'] += $item['counter'];
            $levelNode['children'] = $test($levelNode['children'], $keys, $item);
            return $level;
        };

        $tree = array_reduce($family, function ($memo, $item) use (&$test, $keys) {
            return $test($memo, $keys, $item);
        }, []);

        return $tree;
    }

    /**
     * @param $text
     * @return mixed|string
     */
    static public function slug($text)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * @param $string
     * @return null|string$
     */
    public function stringify($string)
    {
        $transform = explode(' ', $string);

        return implode('-', $transform);
    }

    public function getOrderProductTree($orderProductsList)
    {
        $keys = ['sf'];

        $test = function ($level, $keys, $item) use (&$test) {
            if (!$key = array_shift($keys)) {
                return [];
            }
            $id = $item[$key];

            if (empty($level[$id])) {
                $level[$id] = [
                    'id' => $id,
                    'class' => self::slug($item[$key . '_fr']),
                    'fr' => $item[$key . '_fr'],
                    'children' => []
                ];
            }
            $levelNode = &$level[$id];
            $levelNode['children'] = $test($levelNode['children'], $keys, $item);

            return $level;
        };

        $tree = array_reduce($orderProductsList, function ($memo, $item) use (&$test, $keys) {
            return $test($memo, $keys, $item);
        }, []);

        return $tree;
    }

    /**
     * @param array orderProductsList
     * orderProductList is an array of products:
     * [index]=>
     *    array(17) {
     *        ["code"]=> string
     *        ["denominationProduitBase"]=> string
     *        ["pcb"]=> string
     *        ["ean13"]=> string
     *        ["sf"]=> string
     *        ["sf_fr"]=> string
     *        ["sousFamille"]=> string
     *        ["rayon"]=> string
     *        ["secteur"]=> string
     *        ["dlcMoyenne"]=> string
     *        ["marketingCode"]=> string
     *        ["isPromo"]=> bool
     *        ["libelle"]=> string
     *        ["prixHt"]=> string
     *        ["stock"]=> string
     *        ["natureCode"]=> string
     *        ["quantite"]=> int
     *    }
     *
     */
    public function getCatalogueProductTree($orderProductsList)
    {
        $keys = ['sf'];

        $test = function ($level, $keys, $item) use (&$test) {
            if (!$key = array_shift($keys)) {
                return [];
            }
            $id = $item[$key];

            if (empty($level[$id])) {
                $level[$id] = [
                    'id' => $id,
                    'class' => self::slug($item[$key . '_fr']),
                    'fr' => $item[$key . '_fr'],
                    'children' => []
                ];
            }
            $levelNode = &$level[$id];
            $levelNode['children'] = $test($levelNode['children'], $keys, $item);

            return $level;
        };

        $tree = array_reduce($orderProductsList, function ($memo, $item) use (&$test, $keys) {
            return $test($memo, $keys, $item);
        }, []);

        return $tree;
    }

    /**
     * @return mixed
     */
    public function getPendingOrders($multiplyByPcb)
    {

        $currentUser = $this->getUser();
        $entreprise = $currentUser->getEntrepriseCourante();
        $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $currentUser->getMeta()->getCode(), "entreprise" => $entreprise));
        $orders = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrdersByUser($this->getUser()->getId(), $client->getCode(), $entreprise, $multiplyByPcb, null);

        return $orders;
    }

    /**
     * @return mixed
     */
    public function getUserCgcCpv()
    {
        if (is_object($this->getUser())) {
            $conditions = $this->getUser()->getCgvCpv();
        }

        return $conditions;
    }

    private function refactorOneProduct($product, $currentOrderId,
        // $productsByClientInPromotion, $productsByEnseInPromotion,
        // $promos,
                                        $unitedPromos, $product_basic_info)
    {
        $refactored = [];
        $clientInfo = $this->em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId(), $this->getUser()->getEntrepriseCourante());
        //Quick fix pour reconnaitre la bonne entreprise du bon client pour les precommandes
        $entrepriseOriginal = $this->em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $this->getUser()->getEntrepriseCourante()));
        $codeClientOriginal = $clientInfo['code'];        
        if($entrepriseOriginal->getEntrepriseParent() != null ){
            $codeAs400 = explode("-", $clientInfo['code'])[1];
            $codeClientOriginal = $entrepriseOriginal->getEntrepriseParent()->getCode()."-".$codeAs400;
        }
        //Fin quick fix
        $refactored['code'] = $product->getCode();
        $produitMeti = $this->em->getRepository('SogedialSiteBundle:ProduitMeti')->findOneBy(array('ean13' => $product->getEan13(), 'produit' => $product->getCode(), 'client' => $codeClientOriginal));
        $refactored['produitMeti'] = $produitMeti ? $produitMeti->getProduitMeti() : null;
        $refactored['denominationProduitBase'] = $product->getDenominationProduitBase();
        $refactored['pcb'] = $product->getPcb();
        // pour éviter le risque de division par zéro. Certes, ce sont des données invalides...
        // TODO bloquer niveau ingestion une fois qu'on a compris comment gérer les pcb non-entiers.
        if ($refactored['pcb'] == 0) {
            $refactored['pcb'] = 1;
        }

        $refactored['ean13'] = $product->getEan13();
        $refactored['sf'] = $product->getFamille()->getCode();
        $refactored["sale_unity"] = $product->getSaleUnity();
        $refactored["poid_variable"] = $product->getPoidsVariable() === 'OUI';
        if($this->ms->hasFeature('poid_variable') && $product->getPoidsVariable() === 'OUI') {
            $refactored["poid_variable_note"] = true;
        }
        $refactored['sf_fr'] = $product->getFamille()->getLibelle();
        $product_sf = $product->getSousFamille();
        $refactored['sousFamille'] = ($product_sf ? $product_sf->getLibelle() : "");        // il peut ne pas y avoir des sous-familles
        $refactored['rayon'] = ($product->getRayon() ? $product->getRayon()->getLibelle() : ""); // il peut ne pas y avoir de rayon (cf region 4)
        if($product->getRayon()){
            $refactored['codeRayon'] = $product->getRayon()->getCode();
        }

        $refactored['secteur'] = $product->getSecteur()->getLibelle();
        $refactored['CodeSecteur'] = $product->getSecteur()->getCode();
        $refactored['dlcMoyenne'] = $product->getDlcMoyenne();
        $refactored['marketingCode'] = $product->getMarketingCode();
        $refactored['temperature'] = $product->getTemperature();

        if ($product->getMarque() !== null) {
            $refactored['marque'] = $product->getMarque()->getLibelle();
            if ($refactored['marque'] === 'NON DETERMINE') {
                $refactored['marque'] = '';
            }
        } else {
            $refactored['marque'] = '';
        }

        $refactored['natureCode'] = $product->getNatureCode();

        $refactored['quantite'] = 0;            // pas encore dans le panier

        // Inclure l'entreprise vendeuse pour chaque produit
        $refactored['entreprise'] = $product->getEntreprise()->getRaisonSociale();

        if ($currentOrderId !== null) {           // il existe un panier
            $entityLigne = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->findOneBy(array('commande' => $currentOrderId, 'produit' => $product->getCode()));

            // à quoi ça sert de checker l'id de la commande ? et du produit ????
            if (($entityLigne instanceof LigneCommande) && ($entityLigne->getCommande()->getId() == $currentOrderId) && ($product->getCode() == $entityLigne->getProduit()->getCode())) {
                $refactored['quantite'] = $entityLigne->getQuantite();
            }
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////
        $weightAndVolumeColis = $this->colisService->getWeightAndVolumeColis($product);
        $refactored['poidsColis'] = $weightAndVolumeColis['weight'];
        $refactored['volumeColis'] = $weightAndVolumeColis['volume'];
        // calcul du prix et du stock

        $priceAndStock = $this->ps->getActualProductPriceAndStock($product,
            // $productsByClientInPromotion, $productsByEnseInPromotion, $promos,
            $unitedPromos);
        $refactored['isPromo'] = $priceAndStock['isPromo'];
        $refactored['prixHt'] = $priceAndStock['priceArray'];
        $refactored['stock'] = $priceAndStock['stock'];
        if($product_basic_info["moq"] > 0){
            $refactored["moq"] = $product_basic_info["moq"];
        }

        if($this->getUser()->getPreCommande() === NULL && $refactored['stock'] == 0){
            $refactored['quantite'] = 0;
        }

        if (array_key_exists('promotionCommandeEnCours', $priceAndStock)) {$refactored['promotionCommandeEnCours'] = $priceAndStock['promotionCommandeEnCours'];}
        if (array_key_exists('promotionCommandeFacture', $priceAndStock)) {$refactored['promotionCommandeFacture'] = $priceAndStock['promotionCommandeFacture'];}
        if (array_key_exists('stockInit', $priceAndStock)) {$refactored['stockInit'] = $priceAndStock['stockInit'];}
        if (array_key_exists('EF', $priceAndStock)) {$refactored['EF'] = $priceAndStock['EF'];}

        // fin de calcul de prix et de stock
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        // "totalProduct" donne le total réel

        $refactored["totalPrice"] = $this->ps->getLineItemTotal($refactored["quantite"], $refactored["pcb"], $refactored["prixHt"]);

        // renvoie le prix le plus bas (donc, le dernier)
        $refactored["unitPriceFrom"] = end($refactored["prixHt"]);
        $refactored["packagePriceFrom"] = $refactored["unitPriceFrom"] * $refactored["pcb"];
        $refactored['dureeDeVie'] = $product->getDureeVieJours();
        $refactored['dlcProduit'] = $product->getDlc();
        $refactored['etatProduit'] = $product->getActif();
        $refactored['isClientMeti'] = $clientInfo['isClientMeti'];

        return $refactored;
    }

    /**
     * @param $productRaw
     * @return array
     */
    // prend en entrée un tableau d'objets contenant les informations simples {text, code, ean13, marque}
    public function refactorAllProducts($productRaw)
    {
        $arrayProduct = [];
        $currentUser = $this->getUser();
        // $entityClient = $this->getUser()->getClient();
        // $codeEnseigne = $entityClient->getEnseigne()->getCode();                // TODO move to userinfoservice
        // $codeClient = $entityClient->getCode();
        $currentOrder = $this->em->getRepository('SogedialSiteBundle:Commande')->getCurrentOrderByUser($currentUser, $this->getEntrepriseCodeOrder($currentUser));
        if ($currentOrder !== null) {                       // il existe un panier
            $currentOrderId = $currentOrder->getId();       // pour éviter les appels Doctrine à des niveaux inférieurs
        } else {
            $currentOrderId = null;
        }

        // $productsByClientInPromotion = $this->em->getRepository('SogedialSiteBundle:Produit')->getClientPromotionProductByCodeClient($codeClient);
        // $productsByEnseInPromotion = $this->em->getRepository('SogedialSiteBundle:Produit')->getClientPromotionProductByCodeEnseigne($codeEnseigne);
        // $promos = $this->getPromotionClient();

        //$unitedPromos = $this->ps->getUnitedPromos();
        $unitedPromos = $this->cs->get("sogedial.promotion")->getUnitedPromos();

        foreach ($productRaw as $key => $product_basic_info) {
            $product = $this->em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $product_basic_info['code']));

            $arrayProduct[$key] = $this->refactorOneProduct($product, $currentOrderId,
                // $productsByClientInPromotion, $productsByEnseInPromotion,
                // $promos,
                $unitedPromos,
                $product_basic_info);
                $arrayProduct[$key]["init_price"] = $product_basic_info["init_price"];
                $arrayProduct[$key]["pre_commande"] = $product_basic_info["preCommande"];
                if($product_basic_info["moq"] != 0){
                    $arrayProduct[$key]["moq_client"] = $product_basic_info["moq"];
                } else {
                    $arrayProduct[$key]["moq_client"] = "";
                }
            }

        return $arrayProduct;
    }

    /**
     * @return int
     */
    public function getCatalogueCa()
    {
        $totalCa = 0;
        $results = $this->em
            ->getRepository('SogedialSiteBundle:Commande')
            ->getOrdersToAdmin();

        for ($i = 0; $i < count($results); $i++) {
            $totalCa += $results[$i]['totalPrice'];
        }

        return $totalCa;
    }

    /**
     * @param $multiplyByPcb
     * @return mixed
     */
    public function getOrdersByUser($multiplyByPcb, $limit)
    {
        $arrayPendingOrders = array();
        $currentUser = $this->getUser();
        $entreprise = $currentUser->getEntrepriseCourante();
        $entityClient = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $currentUser->getMeta()->getCode(), "entreprise" => $entreprise));
        $pendingOrders = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrdersToClient($multiplyByPcb, $entreprise, $this->getUser()->getId(), $entityClient->getCode(), $limit);

        foreach ($pendingOrders as $pendingOrder) {
            if($currentUser->getPreCommande() !== NULL && $pendingOrder["codePreCommandeEntreprise"] !== NULL && $pendingOrder["codePreCommandeEntreprise"] !== $currentUser->getEntrepriseCourante()){
                continue;
            }
            $ligneCommande = $this->refactorListOrders($pendingOrder['o_parent'], $pendingOrder['o_temperatureCommande'], $multiplyByPcb);
            $validator = $this->em->getRepository('SogedialUserBundle:User')->findById($pendingOrder["o_validator"]);

            if(is_array($validator) && !empty($validator)){
                $validator = $validator[0];
                if($validator->getEtat() == 'client'){
                    $entityClient = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $validator->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));
                    $validatorName = $entityClient->getNom();
                } else{
                    $validatorName = $validator->getPrenom()." ".$validator->getNom();
                }
                $pendingOrder["validator"] = $validatorName;
            } else {
                $pendingOrder["validator"] = NULL;
            }

            if($pendingOrder['o_temperatureCommande'] === NULL && $ligneCommande[""][0]["totalProducts"] === '0'){
                $arrayPendingOrders[] = [
                    "validator" => $pendingOrder["validator"],
                    "entreprise" => $pendingOrder["entreprise"],
                    "montantFacturation" => $pendingOrder["montantFacturation"],
                    "numero" => $pendingOrder["o_numero"],
                    "" => [
                        0 => [
                            "validator" => $pendingOrder["validator"],
                            "entreprise" => $pendingOrder["entreprise"],
                            "status" => $pendingOrder["libelle"],
                            "id" => $pendingOrder["o_id"],
                            "op_id" => $pendingOrder["o_id"],
                            "status_id" => $pendingOrder["status_id"],
                            "op_quantite" => 0,
                            "op_createdAt" => $pendingOrder["o_createdAt"],
                            "op_prixUnitaire" => 0,
                            "op_montantTotal" => $pendingOrder["montantFacturation"],
                            "op_temperatureProduit" => 0,
                            "numero" => $pendingOrder["o_numero"],
                            "deliveryDate" => $pendingOrder["o_deliveryDate"],
                            "updatedAt" => $pendingOrder["o_updatedAt"],
                            "totalQuantity" => $pendingOrder['totalQuantity'],
                            "totalMass" => 0,
                            "totalMoq" => $pendingOrder["totalMoq"],
                            "totalProducts" => $pendingOrder["totalProducts"],
                            "totalPrice" => ($pendingOrder["o_montantCommande"] == "0.00") ? "NC" : $pendingOrder["o_montantCommande"],
                            "etatProduit" => $pendingOrder["op_actif"]
                        ]
                    ]
                ];
            } else {
                if(isset($ligneCommande["ambient"])){
                    $ligneCommande["ambient"][0]["status_id"] = $pendingOrder["status_id"];
                    $ligneCommande["ambient"][0]["status"] = $pendingOrder["libelle"];
                    $ligneCommande["ambient"][0]["totalPrice"] = $pendingOrder["o_montantCommande"];
                    $ligneCommande["ambient"][0]["etatProduit"] = $pendingOrder["etatProduit"];
                } else if(isset($ligneCommande["negativeCold"])){
                    $ligneCommande["negativeCold"][0]["status_id"] = $pendingOrder["status_id"];
                    $ligneCommande["negativeCold"][0]["status"] = $pendingOrder["libelle"];
                    $ligneCommande["negativeCold"][0]["totalPrice"] = $pendingOrder["o_montantCommande"];
                    $ligneCommande["negativeCold"][0]["etatProduit"] = $pendingOrder["etatProduit"];
                } else if(isset($ligneCommande["positiveCold"])){
                    $ligneCommande["positiveCold"][0]["status_id"] = $pendingOrder["status_id"];
                    $ligneCommande["positiveCold"][0]["status"] = $pendingOrder["libelle"];
                    $ligneCommande["positiveCold"][0]["totalPrice"] = $pendingOrder["o_montantCommande"];
                    $ligneCommande["positiveCold"][0]["etatProduit"] = $pendingOrder["etatProduit"];
                }

                $ligneCommande["totalMoq"] = $pendingOrder["totalMoq"];
                $ligneCommande["validator"] = $pendingOrder["validator"];
                $ligneCommande["entreprise"] = $pendingOrder["entreprise"];
                $ligneCommande["montantFacturation"] = $pendingOrder["montantFacturation"];
                $ligneCommande["numero"] = $pendingOrder["o_numero"];

                $arrayPendingOrders[] = $ligneCommande;
            }
        }

        return $arrayPendingOrders;
    }

    /**
     * @param $parentOrderId
     * @param $temperature
     * @return array
     */
    private function refactorListOrders($parentOrderId, $temperature, $multiplyByPcb)
    {
        $arrayRefactoredOrders = array();
        $arrayRefactoredOrders[$temperature] = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->getLigneByOrderIdAndTemperature($parentOrderId, $temperature, $multiplyByPcb);

        return $arrayRefactoredOrders;
    }

    public function getListAssortimentsByCodeClient($codeClient){
        $listAssortiments = $this->em->getRepository('SogedialSiteBundle:AssortimentClient')->findByClientWithOrder($codeClient);
        return $listAssortiments;
    }

    public function getCurrentAssortimentByCodeClient($codeClient){
        $assortiment = $this->em->getRepository('SogedialSiteBundle:AssortimentClient')->getCurrentAssortimentByCodeClient($codeClient);
        return $assortiment;
    }

    /**
     * @return array
     */
    public function getListSocieteByCodeRegionAndCodeClient($codeClient)
    {
        $listSociete = array();
        $arraySociete = $this->em->getRepository('SogedialSiteBundle:Entreprise')->getListSocieteByCodeRegion($this->ms->getRegion());
        $objetClient =  $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array('code' => $codeClient));

        $arrayAccountClient = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array('code' => $codeClient))->getMeta()->getClients();

        foreach ($arraySociete as $societe) {

            if($societe['codeEntreprise'] == $objetClient->getEntreprise()->getCode()) {
                $listSociete[$objetClient->getEntreprise()->getCode()] = array(
                    $societe['raisonSociale'],
                    $societe['nomEnvironnement'],
                    ($objetClient->isEActif() == true) ? 1 : 0,
                    $objetClient->getEntreprise()->getCode(),
                    'temperatures' => $this->getTemperaturesFromCodeSociete($societe['codeEntreprise']),
                );
            }
        }

        foreach ($arrayAccountClient as $accountClient) {
            $entrepriseAccount = $accountClient->getEntreprise();
            if($entrepriseAccount !== NULL && !array_key_exists($entrepriseAccount->getCode(), $listSociete) && $accountClient->isEActif() == 1){
                $listSociete[$accountClient->getEntreprise()->getCode()] = array(
                    $entrepriseAccount->getRaisonSociale(),
                    $entrepriseAccount->getNomEnvironnement(),
                    1,
                    $entrepriseAccount->getCode(),
                    'temperatures' => $this->getTemperaturesFromCodeSociete($entrepriseAccount->getCode()),
                );
            }
        }

        return $listSociete;
    }

    /**
     * @param string $codeSociete
     * @return array Array of booleans stating whether the given company sells particular temperatures of products.
     */
    private function getTemperaturesFromCodeSociete($codeSociete) {
        $entreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $codeSociete));
        $entrepriseParent = $entreprise->getEntrepriseParent();

        $codeSocieteOrCodeParent = $codeSociete;
        if ($entrepriseParent != NULL) {
            $codeSocieteOrCodeParent = $entrepriseParent->getCode();
        }

        return array(
            'ambient' => $this->ms->hasTemperatureWithCodeEntreprise('ambient', $codeSocieteOrCodeParent),
            'positiveCold' => $this->ms->hasTemperatureWithCodeEntreprise('positiveCold', $codeSocieteOrCodeParent),
            'negativeCold' => $this->ms->hasTemperatureWithCodeEntreprise('negativeCold', $codeSocieteOrCodeParent)
        );
    }

    /**
     * @return mixed
     */
    public function calculateOrderTotalAmount()
    {
        $orderTotalAmount['totalAmount'] = 0;
        $multisite = $this->ms;
        $multiplyByPcb = !($multisite->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;

        $pds = $this->cs->get("sogedial.promotion");
        // On récupère la commande en  cours
        $order = $this->em->getRepository('SogedialSiteBundle:Commande')->getCurrentOrderByUserNewVersion(
            $this->getUser()->getId(),
            $this->getEntrepriseCodeOrder($this->getUser())
        );

        // On récupère les produits qui sont dans la commande
        $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrder($order['o_id'], $multiplyByPcb, $stockColis);

        foreach ($orderProducts as $productRaw) {
            $productObject = $this->em->getRepository('SogedialSiteBundle:Produit')->findOneBy(array('code' => $productRaw["code"]));
            $unitedPromos = $pds->getUnitedPromos();
            $priceAndStock = $this->ps->getActualProductPriceAndStock($productObject, $unitedPromos);
            $prixHt = $priceAndStock['priceArray'];
            $stock = $priceAndStock['stock'];
            $lignesCommande = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->findOneBy(array('commande' => $order['o_id'], 'produit' => $productRaw["code"]));

            if(!($this->getUser()->getPreCommande() !== NULL && $productObject->getPreCommande() === true)){
                //Attention pas de triple = ici car la quantité est sous forme d'un string
                if( $stock == 0){
                    // attention au changement de stock - pas géré pour le moment
                    $sommeRaw = $this->ps->getLineItemTotal(0, $productObject->getPcb(), $prixHt);
                }
                else{
                    $sommeRaw = $this->ps->getLineItemTotal($lignesCommande->getQuantite(), $productObject->getPcb(), $prixHt);
                }
            } else {
                $sommeRaw = $this->ps->getLineItemTotal($lignesCommande->getQuantite(), $productObject->getPcb(), $prixHt);
            }

            $orderTotalAmount['totalAmount'] += $sommeRaw;
        }

        return $orderTotalAmount;
    }

    /**
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function loadProductPage(Request $request, $societe, $page, $tri, $ordertri)
    {
        $query = array();
        $userRepository = $this->em->getRepository('SogedialUserBundle:User');

        if($request->query->get('tri')){
            $query['tri'] = $request->query->get('tri');
        }
        if($request->query->get('ordertri')){
            $query['ordertri'] = $request->query->get('ordertri');
        }

        //Pour savoir si l'on est en mode Promotions ou Nouveauté
        if($request->query->get('kind')){
            $query['kind'] = $request->query->get('kind');
        }

        //Recupere le code secteur s'il existe
        if($request->query->get('codeSecteur')){
            $query['codeSecteur'] = $request->query->get('codeSecteur');
        }

        //Recupere le code rayon s'il existe
        if($request->query->get('codeRayon')){
            $query['codeRayon'] = $request->query->get('codeRayon');
        }

        //Recupere le code famille s'il existe
        if($request->query->get('codeFamille')){
            $query['codeFamille'] = $request->query->get('codeFamille');
        }

        $query['search'] = ($request->query->get('produits')) ? $request->query->get('produits') : '';

        $listProductsByRayon = array();
        $listRayon = array();
        $listProductsResearchByRayon = array();
        $listResearchRayon = array();
        $productRaw = [];

        $limit = 10;
        $limit1 = ($page - 1) * $limit;
        $limit2 = $limit;
        $productResult = $this->rs->getEntriesByQuery($query, $limit1, $limit2);
        $totalProduct = count($productResult);
        $productRaw = $this->refactorAllProducts($productResult);

        //TODO voir si optimisable
        $catalogueProductsByRayon = $this->getCatalogueProductTree($productRaw);
        foreach ($catalogueProductsByRayon as $productByRayon) {
            $listRayon[] = $productByRayon['fr'];
            for ($i = 0; $i < count($productRaw); $i++) {
                if ($productRaw[$i]['sf'] == $productByRayon['id']) {
                    $listProductsByRayon[] = $productRaw[$i];
                }
            }
        }
        //Fin TODO

        $thisPage = $page;
        $OrderTotalAmount = $this->calculateOrderTotalAmount();
        $result2 = $this->getCommande();


        $paramViews = array(
            'societe' => $societe,
            'listProductsByRayon' => $listProductsByRayon,
            'listRayons' => $listRayon,
            'products' => $productRaw,
            'totalProduct' => $totalProduct,
            'thisPage' => $thisPage,
            'tri' => $tri,
            'ordertri' => $ordertri,
            'baseUrl' => $this->cs->getParameter('baseUrl'),
            'request' => $request,
            'commercialInfo' => $userRepository->getCommercialInformation($this->ms->getSociete()),
            'clientInfo' => $userRepository->getClientInformation($this->getUser()->getId()),
            'totalAmount' => $OrderTotalAmount['totalAmount'],
            'poidsTotal' => $result2['poidsTotal'],
            'volumeTotal' => $result2['volumeTotal'],
            'result' => $this->getCommande()
        );

        if(array_key_exists('search', $query) && $query["search"] !== ''){
            $paramViews["countSearch"] = $this->rs->getCountEntriesByQuery($query);
        }

        return $paramViews;
    }

}
