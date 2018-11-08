<?php

namespace Sogedial\SiteBundle\Service;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Exception\NoClientFoundException;
use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\LigneCommande;
use Sogedial\SiteBundle\Entity\RegleMOQ;
use Sogedial\UserBundle\Entity\User;
use Sogedial\UserBundle\Entity\Client;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Sogedial\IntegrationBundle\EventListener\Queues;

class CommandeService
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var EntityManager
     */
    private $em;

    private $multisite;

    private $ps;

    private $sql;

    private $colisService;

    private $validationDayService;


    public function __construct(
        TokenStorage $tokenStorage,
        EntityManager $em,
        MultiSiteService $multisite,
        ProductService $ps,
        SimpleMySQLService $sql,
        AuthorizationCheckerInterface $ac,
        HistoriqueLigneCommandeService $hlcs,
        PromotionService $prs,
        ExportService $es,
        As400CommandeFile $as400,
        ColisService $colisService,
        ValidationDayService $validationDayService
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->multisite = $multisite;
        $this->ps = $ps;
        $this->sql = $sql;
        $this->ac = $ac;
        $this->hlcs = $hlcs;
        $this->prs = $prs;
        $this->es = $es;
        $this->as400 = $as400;
        $this->colisService = $colisService;
        $this->validationDayService = $validationDayService;
    }


    public function getValidatingDate()
    {
        $validatingDate = new \DateTime('now');
        $indexSummerDay = intval($validatingDate->format("N"));
        $validatingDate->add(new \Dateinterval('P' . (8 - $indexSummerDay) . 'D'));

        return $validatingDate;
    }


    public function mapping($commandes)
    {
        $commandesMap = [];
        foreach ($commandes as $k => $v) {
            $commandesMap[] = [
                "montantFacturation" => $v["montantFacturation"],
                "validator" => $v["validator"],
                "numero" => $v["o_numero"],
                "" => [
                    0 => [
                        //"dateFacturation" => $v["o_dateFacturation"],
                        //"numeroFacturation" => $v["o_numeroFacturation"],
                        "status" => $v["libelle"],
                        "id" => $v["o_id"],
                        "op_id" => $v["o_id"],
                        "status_id" => $v["os_id"],
                        "op_quantite" => 0,
                        "op_createdAt" => $v["o_createdAt"],
                        "op_prixUnitaire" => 0,
                        "op_montantTotal" => $v["montantFacturation"],
//                        "op_montantTotal" => $v["o_montantCommande"],
                        "op_temperatureProduit" => 0,
                        "numero" => $v["o_numero"],
                        "deliveryDate" => $v["o_deliveryDate"],
                        "updatedAt" => $v["o_updatedAt"],
                        "totalProducts" => $v["totalProducts"],
                        "totalQuantity" => $v["o_demandeColis"],
                        "totalMass" => $v["colisFacture"],
                        "totalPrice" => ($v["o_montantCommande"] == "0.00") ? "NC" : $v["o_montantCommande"]
//                        "totalPrice" => $v["montantFacturation"],
                    ]
                ]
            ];
        }
        return $commandesMap;
    }

    public function mappingAdminCommandLine($v)
    {
        return [
            0 => [
                "validator" => $v["validatorName"],
                "status" => $v["libelle"],
                "id" => $v["o_id"],
                "op_id" => $v["o_id"],
                "status_id" => $v["os_id"],
                "op_quantite" => 0,
                "op_createdAt" => $v["o_createdAt"],
                "op_prixUnitaire" => 0,
                "op_montantTotal" => $v["o_montantCommande"],
                "op_temperatureProduit" => 0,
                "totalProducts" => $v["totalProducts"],
                "numero" => $v["o_numero"],
                "deliveryDate" => $v["o_deliveryDate"],
                "updatedAt" => $v["o_updatedAt"],
                "totalQuantity" => ($v["o_demandeColis"] === NULL) ? (($v["totalMass"] === NULL) ? NULL : $v["totalMass"]) : $v["o_demandeColis"],
                "totalMass" => $v["colisFacture"],
                "totalMoq" => $v["totalMoq"],
                "entreprise" => $v["entreprise"],
                "totalPrice" => ($v["o_montantCommande"] === "0.00" || $v["o_montantCommande"] === NULL) ? (($v["totalPrice"] === NULL) ? "NC" : $v["totalPrice"]) : $v["o_montantCommande"],
                "nom" => $v["cl_nom"]
            ]
        ];
    }

    public function mappingAdmin($commandes, $multiplyByPcb)
    {
        $commandesMap = [];

        foreach ($commandes as $k => $v) {
            $ligneCommande = $this->refactorListOrders($v['o_parent'], $v['o_temperatureCommande'], $multiplyByPcb);
            $v["validatorName"] = "N/A";

            if (isset($v["o_validator"]) && $v["o_validator"] !== null) {
                $validator = $this->em->getRepository('SogedialUserBundle:User')->getUserFromOrderValidatorId($v["o_validator"]);

                if ($validator->getUser()->getEtat() == 'client') {
                    $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $validator->getUser()->getMeta()->getCode(), "entreprise" => $validator->getUser()->getEntrepriseCourante()));
                    $v["validatorName"] = $client->getNom();
                    $ligneCommande["validator"] = $client->getNom();
                } else {
                    $v["validatorName"] = $validator->getUser()->getPrenom() . " " . $validator->getUser()->getNom();
                    $ligneCommande["validator"] = $validator->getUser()->getPrenom() . " " . $validator->getUser()->getNom();
                }
            }

            if ($v['o_temperatureCommande'] === NULL && $ligneCommande[""][0]["totalProducts"] === '0') {
                $aux = [
                    "montantFacturation" => $v["montantFacturation"],
                    "numero" => $v["o_numero"],
                    "creator" => ($v["clu_nom"] !== NULL) ? $v["clu_nom"] : $v["cl_nom"]
                ];
                $aux[""] = $this->mappingAdminCommandLine($v);
                $commandesMap[] = $aux;
            } else {
                if (isset($ligneCommande["ambient"])) {
                    $v['totalProducts'] = $ligneCommande['ambient'][0]['totalProducts'];
                    $ligneCommande["ambient"] = $this->mappingAdminCommandLine($v);
                } else if (isset($ligneCommande["negativeCold"])) {
                    $v['totalProducts'] = $ligneCommande['negativeCold'][0]['totalProducts'];
                    $ligneCommande["negativeCold"] = $this->mappingAdminCommandLine($v);
                } else if (isset($ligneCommande["positiveCold"])) {
                    $v['totalProducts'] = $ligneCommande['positiveCold'][0]['totalProducts'];
                    $ligneCommande["positiveCold"] = $this->mappingAdminCommandLine($v);
                }

                $ligneCommande["totalMoq"] = $v["totalMoq"];
                $ligneCommande["validator"] = $v["validatorName"];
                $ligneCommande["montantFacturation"] = $v["montantFacturation"];
                $ligneCommande["numero"] = $v["o_numero"];
                $ligneCommande["creator"] = ($v["clu_nom"] !== NULL) ? $v["clu_nom"] : $v["cl_nom"];
                $ligneCommande["entreprise"] = $v["entreprise"];

                $commandesMap[] = $ligneCommande;
            }
        }

        return $commandesMap;
    }

    /**
     * @param $commandes
     * @param $multiplyByPcb
     * @return array
     */
    public function getCommandesAdmin($commandes, $multiplyByPcb)
    {
        return $this->mappingAdmin($commandes, $multiplyByPcb);
    }

    private function getCommandePending($entrepriseCourante)
    {
        $entreprise = "";
        if (strlen($entrepriseCourante) > 0) {
            $entreprise = $entrepriseCourante;
        } else {
            $entreprise = $this->multisite->getSociete();
        }

        $currentOrder = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrderPending($entreprise, $this->tokenStorage->getToken()->getUser()->getId());
        return $currentOrder;
    }

    public function getCommentaireCommandePending($entrepriseCourante = "")
    {
        $currentOrder = $this->getCommandePending($entrepriseCourante);

        if ($currentOrder !== null) {
            return $currentOrder->getCommentaire();
        } else {
            return "";
        }
    }

    public function setCommentaireCommandePending($comment)
    {

        $currentOrder = $this->getCommandePending();

        if ($currentOrder !== null) {
            $currentOrder->setCommentaire($comment);
            $this->em->persist($currentOrder);
            $this->em->flush();
        }
    }

    public function getCommandeCounterAdmin($code_entreprise = false)
    {
        $commandeCounter = array();

        $commandeCounter['all'] = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrderCounterByStatus($code_entreprise, false);
        $commandeCounter['pending'] = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrderCounterByStatus($code_entreprise, 'STATUS_PENDING');
        $commandeCounter['approved'] = $this->em->getRepository('SogedialSiteBundle:Commande')->getOrderCounterByStatus($code_entreprise, 'STATUS_APPROVED');
        return $commandeCounter;
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


    /**
     * Create a "Panier" from a Commande
     *
     * @param Commande $order order to renew
     * @return boolean false if error
     */
    public function orderRenew(Commande $order)
    {
        $entrepriseRepository = $this->em->getRepository(
            'SogedialSiteBundle:Entreprise'
        );
        $commandeRepository = $this->em->getRepository(
            'SogedialSiteBundle:Commande'
        );
        $ligneCommande = $this->em->getRepository(
            'SogedialSiteBundle:LigneCommande'
        );
        $orderStatus = $this->em->getRepository(
            'SogedialSiteBundle:OrderStatus'
        );

        $orderParent = $commandeRepository
            ->findOneBy(array('id' => $order->getParent()));
        $entreprise = $entrepriseRepository
            ->findOneBy(array('code' => $this->multisite->getSociete()));
        $entrepriseInfos = $commandeRepository
            ->getEntrepriseInfosForRecapByOrder($order->getId());

        if ($entrepriseInfos['key'] !== 'STATUS_APPROVED') {
            return false;
        }

        $currentOrder = new Commande();
        $currentOrder->setApplicationOrigine("A7");
        $currentOrder->setUser($orderParent->getUser());
        $currentOrder->setClient($orderParent->getClient());
        $currentOrder->setEntreprise($orderParent->getEntreprise());
        if ($orderParent->getCodePrecommande() !== NULL) {
            $currentOrder->setCodePrecommande($orderParent->getCodePrecommande());
        }

        $this->em->persist($currentOrder);
        $this->em->flush();

        $orderOrderStatus = new OrderOrderStatus();
        $orderOrderStatus->setOrder($currentOrder);
        $orderOrderStatus->setOrderStatus(
            $orderStatus->findOneByKey('STATUS_CURRENT')
        );
        $orderOrderStatus->setCreatedAt(new \DateTime());
        $orderOrderStatus->setUpdatedAt(new \DateTime());
        $this->em->persist($orderOrderStatus);

        $orderProductsToRenew = $ligneCommande
            ->findBy(
                array(
                    'commande' => $order->getParent(),
                    'temperatureProduit' => $order->getTemperatureCommande()
                )
            );

        foreach ($orderProductsToRenew as $orderProductToRenew) {
            $currentOrderProduct = new LigneCommande();
            $currentOrderProduct->setCommande($currentOrder)
                ->setProduit($orderProductToRenew->getProduit())
                ->setQuantite($orderProductToRenew->getQuantite());
            if ($orderProductToRenew->getMOQ() === true) {
                $currentOrderProduct->setMOQ(false);
            }
            $currentOrderProduct->setPrixUnitaire(
                round(
                    $orderProductToRenew->getPrixUnitaire(),
                    2,
                    PHP_ROUND_HALF_DOWN
                )
            );
            $currentOrderProduct->setMontantTotal(
                round(
                    $orderProductToRenew->getMontantTotal(),
                    2,
                    PHP_ROUND_HALF_DOWN
                )
            );
            $currentOrderProduct->setTemperatureProduit(
                $order->getTemperatureCommande()
            );

            $weightVolume = $this->colisService->getWeightAndVolumeColis($orderProductToRenew->getProduit());
            $weightVolumeTotal = $this->colisService->getVolumeWeightItemTotal($orderProductToRenew->getProduit(), $orderProductToRenew->getQuantite());

            $orderProductToRenew->setPoidsUnitaire($weightVolume['weight']);
            $orderProductToRenew->setVolumeUnitaire($weightVolume['volume']);

            $orderProductToRenew->setPoidsTotal($weightVolumeTotal['weightTotal']);
            $orderProductToRenew->setVolumeTotal($weightVolumeTotal['volumeTotal']);

            $this->em->persist($currentOrderProduct);
            $this->em->flush();
        }

        return true;
    }

    /**
     * @todo: make another service
     */

    public function checkMOQColis(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {
        return false;
    }

    public function checkMOQEuros(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {
        return $ligneCommande->getMontantTotal() > $regleMOQ->getQuantiteMinimale();
    }

    public function checkMOQKilograme(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {
        $colisRepository = $this->em->getRepository(
            'SogedialSiteBundle:Colis'
        );

        $colis = $colisRepository->findOneBy(array(
            "produit" => $ligneCommande->getProduit()
        ));

        if ($colis === null) {
            return false;
        }

        $weight = $ligneCommande->getQuantite() * $colis->getPoidsBrutUVC();

        return $weight > $regleMOQ->getQuantiteMinimale();
    }

    public function checkMOQPalette(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {
        return false;
    }

    public function checkMOQUC(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {
        return $ligneCommande->getQuantite() > $regleMOQ->getQuantiteMinimale();
    }

    public function checkMOQGroup(
        LigneCommande $ligneCommande, RegleMOQ $regleMOQ
    )
    {

    }

    /**
     * @param LigneCommande ligneCommande
     * @return boolean
     */
    public function handleMOQLigneCommand(LigneCommande $ligneCommande)
    {
        $ligneCommandeRepository = $this->em->getRepository(
            'SogedialSiteBundle:LigneCommande'
        );

        //$regleMOQ = $ligneCommandeRepository
        //    ->getMOQRuleFromProductOfLigneCommande($ligneCommande);

        //what the differece ?
        if ($regleMOQ->getGroup() || $regleMOQ->getMix()) {

        }

        //no moq associated to the product
        if (!$regleMOQ) {
            return false;
        }

        switch ($regleMOQ->getUnite()) {
            case 'col':
                return $this->checkMOQColis($ligneCommande, $regleMOQ);
            case 'euros':
                return $this->checkMOQEuros($ligneCommande, $regleMOQ);
            case 'kg':
                return $this->checkMOQKilograme($ligneCommande, $regleMOQ);
            case 'pal':
                return $this->checkMOQPalette($ligneCommande, $regleMOQ);
            case 'uc':
                return $this->checkMOQUC($ligneCommande, $regleMOQ);
        }

        return new \Exception("unknow MOQ rule");
    }

    public function setMontantCommandFromCommandLines($commande_code, $parent_commande_code)
    {
        return $this->sql->query('UPDATE commande SET montant_commande = ( SELECT SUM( montant_total )
        FROM ligneCommande
        WHERE commande_id = ' . $parent_commande_code . ' )
        WHERE id = ' . $commande_code);
    }

    public function setLignesCommandesInformations($orderId)
    {
        $lignesCommande = $this->em->getRepository(
            'SogedialSiteBundle:LigneCommande'
        )->findBy(array("commande" => $orderId));

        foreach ($lignesCommande as $ligneCommande) {
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
            $ligneCommande->setStock($ligneCommande->getProduit()->getStock());
            $this->em->persist($ligneCommande);
        }
        $this->em->flush();
    }

    public function getCommandesMOQ($status, $societe)
    {
        $commandesRepository = $this->em->getRepository(
            'SogedialSiteBundle:Commande'
        );

        $commandes = $commandesRepository->getCommandesMOQ($status, $societe);
        $suppliers = array(
            "suppliers" => array(),
            "validated" => $commandesRepository->countCommandLineByMOQStatus("VALIDATED", $societe),
            "tovalid" => $commandesRepository->countCommandLineByMOQStatus("VALID", $societe)
        );

        foreach ($commandes as $commande) {
            if (!isset($suppliers["suppliers"][$commande["supplier"]])) {
                $suppliers["suppliers"][$commande["supplier"]] = array();
            }

            $rule = explode(" ", $commande["ruleName"]);
            $ruleQuantity = $rule[0];
            $ruleUnity = $rule[1];

            switch ($ruleUnity) {
                case 'col':
                    $commande["ruleName"] = $ruleQuantity . " colis";
                    break;
                case 'uc':
                    $commande["ruleName"] = $ruleQuantity . " unités";
                    break;
                case 'pal':
                    $commande["ruleName"] = $ruleQuantity . " palettes";
                    break;
            }

            if ($commande["ruleGroup"]) {
                $commande["ruleName"] .= " groupé";
            }
            if ($commande["ruleMix"]) {
                $commande["ruleName"] .= " mixe";
            }

            $commande["weight"] = floatval($commande['weight']);
            $commande["pcb"] = intval($commande["pcb"]);

            if (!isset($suppliers["suppliers"][$commande["supplier"]][$commande["rule"]])) {
                if ($commande["ruleMix"]) {
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]] = array(
                        "name" => $commande["ruleName"],
                        "products" => array(),
                        "status" => $commande["moqStatus"],
                        "totalGlobal" => 0,
                        "totalGlobalUC" => 0,
                        "totalGlobalPrice" => 0,
                        "totalGlobalWeight" => 0,
                        "totalGlobalPallet" => 0,
                        'totalPerProduct' => 0
                    );
                } else {
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]] = array(
                        "name" => $commande["ruleName"],
                        "products" => array(),
                        "status" => $commande["moqStatus"],
                    );
                }
            }

            $othersQuantity = 0;
            if (!isset($suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]])) {
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]] = array(
                    'price' => $commande["price"],
                    'pcb' => $commande["pcb"],
                    'pallet' => intval($commande["pallet"]), // not alway defined
                    'weight' => $commande["weight"],
                    'ean13' => $commande["ean13"],
                    "status" => $commande["moqStatus"],
                    'users' => array(),
                    "totalGlobal" => 0,
                    "totalGlobalUC" => 0,
                    "totalGlobalPrice" => 0,
                    "totalGlobalWeight" => 0,
                    "totalGlobalPallet" => 0,
                    'totalPerProduct' => 0
                );
            }

            if (!isset($suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["users"][$commande["commandeLineCode"]])) {
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["users"][$commande["commandeLineCode"]] = array(
                    'quantity' => $commande["quantity"],
                    'code' => $commande["commandeLineCode"],
                    'moq' => $commande["moqStatus"],
                    "user" => $commande["user"],
                    "commande" => $commande["commande"],
                    "client" => $commande["clientName"] . " - numéro de commande " . $commande["commande_numero"],
                    "moq_quantity" => $commande["client_produit_moq_quantity"]
                );

                if ($commande["ruleGroup"] && !array_key_exists("othersQuantity", $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]])) {
                    $othersQuantity = $commandesRepository->sumOthersSocietiesQuantitiesProduct($status, $societe, $commande["rule"], $commande["ean13"]);
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["othersQuantity"] = $othersQuantity;
                    //$commande["quantity"] += $othersQuantity;
                }

                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalPerProduct"] += $commande["quantity"];
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalGlobal"] += $commande["quantity"];
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalGlobalUC"] += $commande["quantity"] * $commande["pcb"];
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalGlobalPrice"] += $commande["price"] * $commande["quantity"] * $commande["pcb"];
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalGlobalWeight"] += $commande["quantity"] * $commande["weight"];
                if ($commande["pallet"] > 0) {
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["totalGlobalPallet"] += $commande["quantity"] / $commande["pallet"];
                }
                if ($commande["ruleMix"]) {
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalPerProduct"] += $commande["quantity"];
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalGlobal"] += $commande["quantity"];
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalGlobalUC"] += $commande["quantity"] * $commande["pcb"];
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalGlobalPrice"] += $commande["price"] * $commande["quantity"] * $commande["pcb"];
                    $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalGlobalWeight"] += $commande["quantity"] * $commande["weight"];
                    if ($commande["pallet"] > 0) {
                        $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["totalGlobalPallet"] += $commande["quantity"] / $commande["pallet"];
                    }
                }
                $suppliers["suppliers"][$commande["supplier"]][$commande["rule"]]["products"][$commande["product_name"]]["status"] &= $commande["moqStatus"];
            }
        }

        return $suppliers;
    }

    /**
     * @param date "ddmmyyyy"
     * @return dd-mm-yyyy
     */
    public function dateFormatHelper($date)
    {
        if ($date === '') {
            return null;
        }

        $day = substr($date, 0, 2);
        $month = substr($date, 2, 2);
        $year = substr($date, -4);

        return sprintf('%s-%s-%s', $year, $month, $day);
    }

    public function getValidatorCommande(User $currentUser)
    {
        if ($this->ac->isGranted('ROLE_PREVIOUS_ADMIN')) {
            foreach ($this->tokenStorage->getToken()->getRoles() as $role) {
                $roleClassName = get_class($role);
                $switchClassName = 'Symfony\Component\Security\Core\Role\SwitchUserRole';
                if ($roleClassName === $switchClassName) {
                    return $this->em->getRepository('SogedialUserBundle:User')->findOneById($role->getSource()->getUser()->getId());
                }
            }
        }

        return $this->em->getRepository('SogedialUserBundle:User')->findOneById($currentUser->getId());
    }

    private function getClientOrder(Commande $order, User $user)
    {
        $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $user->getMeta()->getCode(), "entreprise" => $order->getEntreprise()->getCode()));
        if ($client === NULL) {
            $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $user->getMeta()->getCode(), "entreprise" => $user->getEntrepriseCourante()));
        }

        return $client;
    }

    public function exportOrders(array $suborders, Commande $order, User $user, $comment, $ambientDeliveryDate)
    {
        $client = null;

        //for each temperature sub-order
        foreach ($suborders as $order) {
            $clientName = "";
            if ($order !== null) {
                if ($client === null) {
                    $client = $this->getClientOrder($order, $user);
                }
                $clientName = $client->getNom();
            } else {
                $clientName = $order->getClient()->getNom();
            }

            $this->es->generatePdfForOrder($order, $clientName, $comment, $ambientDeliveryDate);
            $this->es->sendFrancoMail($order->getNumero(), $order->getMontantCommande(), $clientName, $comment);
        }
    }

    public function updateOrderStatus(Commande $order, $statusKey, \DateTime $createdAt, \DateTime $updatedAt)
    {
        // Update order order status
        $orderStatus = $this->em->getRepository('SogedialSiteBundle:OrderStatus')->findOneByKey($statusKey);
        $orderOrderStatus = $this->em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneByOrder($order->getId());
        $orderOrderStatus->setOrder($order);
        $orderOrderStatus->setOrderStatus($orderStatus);
        $orderOrderStatus->setCreatedAt($createdAt);
        $orderOrderStatus->setUpdatedAt($updatedAt);
        $this->em->persist($orderOrderStatus);
        $this->em->flush();
    }

    /**
     * Création des commandes sous commandes liées à la commande $order
     */
    public function createSubOrders(User $user, Commande $order, array $products, array $productsKeys, $comment, $validator, $validatingDate, \DateTime $createdAt, \DateTime $updatedAt, $ambientDeliveryDate, $positiveColdDeliveryDate, $negativeColdDeliveryDate)
    {
        $i = 0;
        $childOrder = [];
        foreach ($productsKeys as $item) {
            if ($products[$item] && count($products[$item]['products'] > 0)) {
                $childOrder[$i] = new Commande();

                if ($user->getPreCommande() !== NULL && $order->getCodePrecommande() !== NULL) {
                    $childOrder[$i]->setCodePrecommande($order->getCodePrecommande());
                }

                $childOrder[$i]->setEntreprise($order->getEntreprise());
                $childOrder[$i]->setParent($order->getId());
                $childOrder[$i]->setTemperatureCommande($item);
                $childOrder[$i]->setUser($user);
                $childOrder[$i]->setUpdatedAt(new \DateTime('now'));
                $childOrder[$i]->setCommentaire($comment);
                $childOrder[$i]->setValidator($validator);
                $childOrder[$i]->setApplicationOrigine("A7");
                $childOrder[$i]->setValidatingDate($validatingDate);
                $lignesCommandeSumAndQuantity = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->getQuantityAndSumByOrderIdAndTemperature(
                    $order->getId(),
                    $childOrder[$i]->getTemperatureCommande()
                );


                $ligneCommandeWeightAndVolume = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->getWeightSumAndVolumeSumByOrderIdAndTemperature(
                    $order->getId(),
                    $childOrder[$i]->getTemperatureCommande()
                );
                $childOrder[$i]->setVolumeCommande($ligneCommandeWeightAndVolume["totalVolume"]);
                $childOrder[$i]->setPoidsCommande($ligneCommandeWeightAndVolume["totalWeight"]);

                $childOrder[$i]->setMontantCommande($lignesCommandeSumAndQuantity["totalSum"]);
                $childOrder[$i]->setDemandeColis($lignesCommandeSumAndQuantity["totalQuantity"]);

                if ($user->getPreCommande() === NULL && $this->multisite->hasFeature('date-panier')) {
                    if ($item == 'ambient' && $ambientDeliveryDate !== null) {
                        $childOrder[$i]->setDeliveryDate(new \DateTime($ambientDeliveryDate));
                    }

                    if ($item == 'positiveCold' && $positiveColdDeliveryDate !== null) {
                        $childOrder[$i]->setDeliveryDate(new \DateTime($positiveColdDeliveryDate));
                    }

                    if ($item == 'negativeCold' && $negativeColdDeliveryDate !== null) {
                        $childOrder[$i]->setDeliveryDate(new \DateTime($negativeColdDeliveryDate));
                    }
                } else if ($user->getPreCommande() !== NULL) {
                    $childOrder[$i]->setDeliveryDate($this->validationDayService->getNextDeliveryDate($order->getEntreprise()));
                } else if (!$this->multisite->hasFeature('date-panier')) {
                    $childOrder[$i]->setDeliveryDate(null);
                }
                $this->em->persist($childOrder[$i]);

                //Create the corresponding orderOrderStatus for the current childOrder
                $orderOrderStatus = new OrderOrderStatus();
                $orderOrderStatus->setOrder($childOrder[$i]);

                $moqElement = $this->em->getRepository('SogedialSiteBundle:LigneCommande')->findBy(array('commande' => $order->getId(), 'moq' => 0));

                //TODO : A supprimer lors que la validation des moq sera en réactivée
                $orderOrderStatus->setOrderStatus(
                    $this->em->getRepository('SogedialSiteBundle:OrderStatus')
                        ->findOneByKey('STATUS_APPROVED')
                );

                // Was asked to be disabled by our client ("Le Metier") @Sictoz
//                if(count($moqElement) > 0){
//                    $orderOrderStatus->setOrderStatus(
//                        $this->em->getRepository('SogedialSiteBundle:OrderStatus')
//                            ->findOneByKey('STATUS_PENDING')
//                    );
//                }
//                else{
//                    $orderOrderStatus->setOrderStatus(
//                        $this->em->getRepository('SogedialSiteBundle:OrderStatus')
//                            ->findOneByKey('STATUS_APPROVED')
//                    );
//                }

                $orderOrderStatus->setCreatedAt($createdAt);
                $orderOrderStatus->setUpdatedAt($updatedAt);
                $this->em->persist($orderOrderStatus);
                $i++;
            }
        }
        $this->em->flush();

        return $childOrder;
    }

    /**
     * 1) Delete empty ligne commande (with 0 quantities)
     * 2) Append lignecommande quantity to history
     * 3) Set new stock promotion (minus by ligneCommande->quantity)
     * 4) Snapshot LigneCommande
     */
    public function setLigneCommandes(array $orderProducts, array $clientInfos, Commande $order, User $user)
    {

        $today = new \DateTime('now');
        $countOrderProducts = (int)count($orderProducts);
        $promotionProducts = array();
        $products = [
            'ambient' => [],
            'positiveCold' => [],
            'negativeColde' => []
        ];

        // Delete quntity = 0 from LigneCommande
        //$this->em->getRepository('SogedialSiteBundle:LigneCommande')->deleteEmptyLignesCommande($order->getId());

        // Delete none actif products from LigneCommande
        //$this->em->getRepository('SogedialSiteBundle:LigneCommande')->clearPanierInactifProducts($order->getId());

//        $codeRegion = substr($order->getEntreprise()->getCode(), 0, -2);
//        $arrayCodeRegionExcluded = array('3', '4');
//        if (($user->getPreCommande() === 'NULL') && !(in_array($codeRegion, $arrayCodeRegionExcluded))) {
//            // Delete stockless products from LigneCommande
//            $this->em->getRepository('SogedialSiteBundle:LigneCommande')->clearStocklessProducts($order->getId());
//        }

        for ($i = 0; $i < $countOrderProducts; $i++) {
            $lignesCommande = $this->em->getRepository('SogedialSiteBundle:LigneCommande')
                ->findOneBy(array('commande' => $order->getId(), 'produit' => $orderProducts[$i]["code"]));

            //if ligne command have been deleted, Find will get NULL
            if ($lignesCommande === NULL) {
                array_push($promotionProducts, []);
                continue;
            }

            $promotion = $this->em->getRepository('SogedialSiteBundle:Promotion')->findBy(array(
                'client' => $clientInfos["code"],
                'produit' => $orderProducts[$i]["code"]

            ));

            // Update de ligne de commande pour tous les produits du panier en cours
            $product = $this->em->getRepository('SogedialSiteBundle:Produit')->findOneBy(array('code' => $orderProducts[$i]["code"]));

            // Recuperation dans la table colis du poids et volume du produit
            $weightVolume = $this->colisService->getWeightAndVolumeColis($product);
            $weightVolumeTotal = $this->colisService->getVolumeWeightItemTotal($product, $lignesCommande->getQuantite());

            $lignesCommande->setPoidsUnitaire($weightVolume['weight']);
            $lignesCommande->setVolumeUnitaire($weightVolume['volume']);

            $lignesCommande->setPoidsTotal($weightVolumeTotal['weightTotal']);
            $lignesCommande->setVolumeTotal($weightVolumeTotal['volumeTotal']);

            if ($lignesCommande !== NULL) {
                $historiqueLigneCommande = $this->hlcs->create(
                    $lignesCommande,
                    $user,
                    $lignesCommande->getQuantite()
                );
                $this->em->persist($historiqueLigneCommande);
            }

            if (!($user->getPreCommande() !== NULL && $product->getPreCommande() === true)) {
                $unitedPromos = $this->prs->getUnitedPromos();
                $priceAndStock = $this->ps->getActualProductPriceAndStock($product, $unitedPromos);
                $prixHt = $priceAndStock['priceArray'];
                $stock = $priceAndStock['stock'];

                $lignesCommande->setPrixUnitaire($this->ps->getDegressivePrice($lignesCommande->getQuantite(), $prixHt));
                //Attention pas de triple = ici car la quantité est sous forme d'un string
                if ($stock == 0) {
                    // attention au changement de stock - pas géré pour le moment
                    $sommeRaw = $this->ps->getLineItemTotal(0, $product->getPcb(), $prixHt);
                    $lignesCommande->setMontantTotal($sommeRaw);
                } else {
                    $sommeRaw = $this->ps->getLineItemTotal($lignesCommande->getQuantite(), $product->getPcb(), $prixHt);
                    $lignesCommande->setMontantTotal($sommeRaw);
                }

                $this->em->persist($lignesCommande);
                // FIN - Update de ligne de commande pour tous les produits du panier en cours
            }

            if (count($promotion) === 1 && $today > $promotion[0]->getDateDebutValidite() && $today < $promotion[0]->getDateFinValidite()) {
                array_push($promotionProducts,
                    $promotion
                );
            } else {
                array_push($promotionProducts,
                    []
                );
            }

            if (($orderProducts[$i]['temperature'] === "SEC") && $orderProducts[$i]['quantite'] > 0) {
                $products['ambient']['products'][] = $orderProducts[$i];
            }

            if (($orderProducts[$i]['temperature'] === "FRAIS") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['positiveCold']['products'][] = $orderProducts[$i];
            }

            if (($orderProducts[$i]['temperature'] === "SURGELE") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['negativeCold']['products'][] = $orderProducts[$i];
            }
        }
        $this->em->flush();

        for ($i = 0; $i < $countOrderProducts; $i++) {
            if (count($promotionProducts[$i]) === 1 && $promotionProducts[$i][0]->getCodeTypePromo() === 'EF') {
                $promotionProducts[$i][0]->setStockEngagementRestant($promotionProducts[$i][0]->getStockEngagementRestant() - $orderProducts[$i]["quantite"]);
                $this->em->persist($promotionProducts[$i][0]);
            }
        }
        $this->em->flush();

        $this->setLignesCommandesInformations($order->getId());

        return $products;
    }

    /**
     * @param array $orderProducts
     * @param array $clientInfos
     * @param Commande $order
     * @param User $user
     * @return array
     */
    public function setLigneCommandesForConsole(array $orderProducts, array $clientInfos, Commande $order, User $user)
    {
        $products = $this->setProductsPricingForConsole($orderProducts, $clientInfos, $order, $user);

        $this->setLignesCommandesInformations($order->getId());

        return $products;
    }

    /**
     * @param array $orderProducts
     * @param array $clientInfos
     * @param Commande $order
     * @param User $user
     * @return array
     */
    public function setProductsPricingForConsole(array $orderProducts, array $clientInfos, Commande $order, User $user)
    {
        $today = new \DateTime('now');
        $countOrderProducts = (int)count($orderProducts);
        $promotionProducts = array();
        $products = [
            'ambient' => [],
            'positiveCold' => [],
            'negativeColde' => []
        ];
        $codeSociete = $order->getEntreprise()->getCode();

        //$this->em->getRepository('SogedialSiteBundle:LigneCommande')->deleteEmptyLignesCommande($order->getId());

        // Delete none actif products from LigneCommande
        //$this->em->getRepository('SogedialSiteBundle:LigneCommande')->clearPanierInactifProducts($order->getId());

        /*$codeRegion = substr($order->getEntreprise()->getCode(), 0, -2);
        $arrayCodeRegionExcluded = array('3', '4');*/

        /*if (($user->getPreCommande() === 'NULL') && !(in_array($codeRegion, $arrayCodeRegionExcluded))) {
            // Delete stockless products from LigneCommande
            $this->em->getRepository('SogedialSiteBundle:LigneCommande')->clearStocklessProducts($order->getId());
        }*/

        for ($i = 0; $i < $countOrderProducts; $i++) {
            $lignesCommande = $this->em->getRepository('SogedialSiteBundle:LigneCommande')
                ->findOneBy(array('commande' => $order->getId(), 'produit' => $orderProducts[$i]["code"]));

            //if ligne command have been deleted, Find will get NULL
            if ($lignesCommande === NULL) {
                array_push($promotionProducts, []);
                continue;
            }

            $promotion = $this->em->getRepository('SogedialSiteBundle:Promotion')->findBy(array(
                'client' => $clientInfos["code"],
                'produit' => $orderProducts[$i]["code"]

            ));

            // Update de ligne de commande pour tous les produits du panier en cours
            $product = $this->em->getRepository('SogedialSiteBundle:Produit')->findOneBy(array('code' => $orderProducts[$i]["code"]));

            // Recuperation dans la table colis du poids et volume du produit
            $weightVolume = $this->colisService->getWeightAndVolumeColis($product);
            $weightVolumeTotal = $this->colisService->getVolumeWeightItemTotal($product, $lignesCommande->getQuantite());

            $lignesCommande->setPoidsUnitaire($weightVolume['weight']);
            $lignesCommande->setVolumeUnitaire($weightVolume['volume']);

            $lignesCommande->setPoidsTotal($weightVolumeTotal['weightTotal']);
            $lignesCommande->setVolumeTotal($weightVolumeTotal['volumeTotal']);

            if ($lignesCommande !== NULL) {
                $historiqueLigneCommande = $this->hlcs->create(
                    $lignesCommande,
                    $user,
                    $lignesCommande->getQuantite()
                );
                $this->em->persist($historiqueLigneCommande);
            }

            if (!($user->getPreCommande() !== NULL && $product->getPreCommande() === true)) {
                $unitedPromos = $this->prs->getUnitedPromos();
                $priceAndStock = $this->ps->getActualProductPriceAndStockForConsole($product, $unitedPromos, $user, $codeSociete);
                $prixHt = $priceAndStock['priceArray'];
                $stock = $priceAndStock['stock'];

                $lignesCommande->setPrixUnitaire($this->ps->getDegressivePrice($lignesCommande->getQuantite(), $prixHt));
                //Attention pas de triple = ici car la quantité est sous forme d'un string
                if ($stock == 0) {
                    // attention au changement de stock - pas géré pour le moment
                    $sommeRaw = $this->ps->getLineItemTotal(0, $product->getPcb(), $prixHt);
                    $lignesCommande->setMontantTotal($sommeRaw);
                } else {
                    $sommeRaw = $this->ps->getLineItemTotal($lignesCommande->getQuantite(), $product->getPcb(), $prixHt);
                    $lignesCommande->setMontantTotal($sommeRaw);
                }

                $this->em->persist($lignesCommande);
                // FIN - Update de ligne de commande pour tous les produits du panier en cours
            }

            if (count($promotion) === 1 && $today > $promotion[0]->getDateDebutValidite() && $today < $promotion[0]->getDateFinValidite()) {
                array_push($promotionProducts,
                    $promotion
                );
            } else {
                array_push($promotionProducts,
                    []
                );
            }

            if (($orderProducts[$i]['temperature'] === "SEC") && $orderProducts[$i]['quantite'] > 0) {
                $products['ambient']['products'][] = $orderProducts[$i];
            }

            if (($orderProducts[$i]['temperature'] === "FRAIS") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['positiveCold']['products'][] = $orderProducts[$i];
            }

            if (($orderProducts[$i]['temperature'] === "SURGELE") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['negativeCold']['products'][] = $orderProducts[$i];
            }
        }
        $this->em->flush();

        for ($i = 0; $i < $countOrderProducts; $i++) {
            if (count($promotionProducts[$i]) === 1 && $promotionProducts[$i][0]->getCodeTypePromo() === 'EF') {
                $promotionProducts[$i][0]->setStockEngagementRestant($promotionProducts[$i][0]->getStockEngagementRestant() - $orderProducts[$i]["quantite"]);
                $this->em->persist($promotionProducts[$i][0]);
            }
        }
        $this->em->flush();

        return $products;
    }

    /**
     * @param Commande $order
     * @param User $user
     * @param $dateLivraison
     * @param $comment
     * @return \DateTime
     */
    public function validateCommandeOrder(Commande $order, User $user, $dateLivraison, $comment)
    {
        //Update order with some information
        if ($order instanceof Commande) {
            $order->setDatesString($dateLivraison);
            $order->setCommentaire($comment);
            $order->setUser($user);
            $order->setValidator($user);

            $this->em->persist($order);
            $this->em->flush();
        }

        $now = new \DateTime();
        $this->updateOrderStatus($order, 'STATUS_BASKET_PENDING', $now, $now);

        return $this->getValidatingDate();
    }

    /**
     * @param $multiplyByPcb
     * @param $code_entreprise
     * @param $limit
     * @return array
     */
    public function getPendingOrdersToAdminDashboard($multiplyByPcb, $code_entreprise, $limit)
    {
        $adminPendingOrders = $this->em->getRepository('SogedialSiteBundle:Commande')->getBasketToAdmin($multiplyByPcb, $code_entreprise, $limit);

        return $adminPendingOrders;
    }
}