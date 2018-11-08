<?php

namespace Sogedial\IntegrationBundle\Controller;

use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Sogedial\SiteBundle\Entity\Produit;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sogedial\IntegrationBundle\Form\Type\ResearchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sogedial\IntegrationBundle\EventListener\Queues;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CommandeController extends Controller
{

    public function panierValidateAction()
    {
        $order = $this->get('sogedial_integration.catalogue')->getCommande();
        $panier_is_validate = (null == $order) || ($order && $order["order"] && !$order["order"]["o_id"]);
        return new JsonResponse(array('panier_is_validate' => $panier_is_validate));
    }


    private function js_array($array)
    {

        $js_str = function ($s) {
            return "'" . addcslashes($s, "\0..\37\"\\") . "'";
        };

        $temp = array_map($js_str, $array);
        return "[" . implode(",", $temp) . "]";
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function panierAction(Request $request, $societe)
    {
        $em = $this->getDoctrine()->getManager();
        $arrayHoliday = array();
        $currentUser = $this->getUser();
        $entityClient = $em->getRepository('SogedialSiteBundle:Client')->getClientObject($currentUser->getMeta()->getCode(), $currentUser->getEntrepriseCourante());
        $isEactif = ($entityClient->isEActif() === true) ? 1 : 0 ;
        $codeClient = $entityClient->getCode();
        $ps = $this->get('sogedial.product');
        $multisite = $this->get('sogedial.multisite');
        $order = $this->get('sogedial_integration.catalogue')->getCommande();
        $multiplyByPcb = !($multisite->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;
        $listProductsByRayon = array();
        $listRayon = array();

        if (null == $order || ($order && $order["order"] && !$order["order"]["o_id"])) {
            return $this->redirect($this->generateUrl('sogedial_integration_catalogue', array('societe' => $societe)));
        }

        $orderProducts = $em->getRepository('SogedialSiteBundle:Produit')->getPanierListProducts($order["order"]["o_id"], $multiplyByPcb, $stockColis, $codeClient);

        $productsByRayon = $this->get('sogedial_integration.catalogue')->getOrderProductTree($orderProducts);
        $unitedPromos = $this->get("sogedial.promotion")->getUnitedPromos();

        $entrepriseInfos = $em->getRepository('SogedialSiteBundle:Commande')->getEntrepriseInfosForRecapByOrder($order["order"]["o_id"]);
        $clientInfos = $em->getRepository('SogedialSiteBundle:Commande')->getClientInfosForRecapByOrder($order["order"]["o_id"]);
        $clientNameUrl = $this->get('sogedial_integration.catalogue')->stringify($clientInfos['nom']);

        $products['ambient'] = [];
        $products['positiveCold'] = [];
        $products['negativeCold'] = [];

        $products['ambient']['products'] = [];
        $products['ambient']['sumColis'] = 0;
        $products['ambient']['sumPrice'] = 0;

        $products['positiveCold']['products'] = [];
        $products['positiveCold']['sumColis'] = 0;
        $products['positiveCold']['sumPrice'] = 0;

        $products['negativeCold']['products'] = [];
        $products['negativeCold']['sumColis'] = 0;
        $products['negativeCold']['sumPrice'] = 0;
        $totalItems = (int)count($orderProducts);

        for ($i = 0; $i < $totalItems; $i++) {

            $product = $em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $orderProducts[$i]["code"]));

            $priceAndStock = $ps->getActualProductPriceAndStock($product, $unitedPromos);

            $orderProducts[$i]['isPromo'] = $priceAndStock['isPromo'];
            $orderProducts[$i]['prixHt'] = $priceAndStock['priceArray'];
            $orderProducts[$i]['stock'] = $priceAndStock['stock'];

            if(($orderProducts[$i]['stock'] === '0' || $orderProducts[$i]['etatProduit'] === '0' ) && $this->getUser()->getPreCommande() === 'NULL') {
                $orderProducts[$i]['totalPrice'] = 0;
            }

            if (array_key_exists('promotionCommandeEnCours', $priceAndStock)) {
                $orderProducts[$i]['promotionCommandeEnCours'] = $priceAndStock['promotionCommandeEnCours'];
            }
            if (array_key_exists('promotionCommandeFacture', $priceAndStock)) {
                $orderProducts[$i]['promotionCommandeFacture'] = $priceAndStock['promotionCommandeFacture'];
            }
            if (array_key_exists('stockInit', $priceAndStock)) {
                $orderProducts[$i]['stockInit'] = $priceAndStock['stockInit'];
            }
            if (array_key_exists('EF', $priceAndStock)) {
                $orderProducts[$i]['EF'] = $priceAndStock['EF'];
            }

            //Attention pas de triple = ici car la quantité est sous forme d'un string
            /*
            if($statutOrder->getKey() === 'STATUS_CURRENT' && $orderProducts[$i]["stock"] == 0){
                // attention au changement de stock - pas géré pour le moment
                $orderProducts[$i]["totalPrice"] = $ps->getLineItemTotal(0, $orderProducts[$i]["pcb"], $orderProducts[$i]["prixHt"]);
            }
            else{
                $orderProducts[$i]["totalPrice"] = $ps->getLineItemTotal($orderProducts[$i]["quantite"], $orderProducts[$i]["pcb"], $orderProducts[$i]["prixHt"]);
            }
            */

            // renvoie le prix le plus bas (donc, le dernier)
            $orderProducts[$i]["unitPriceFrom"] = end($orderProducts[$i]["prixHt"]);
            $orderProducts[$i]["packagePriceFrom"] = $orderProducts[$i]["unitPriceFrom"] * $orderProducts[$i]["pcb"];

            // ($orderProducts[$i]['stock'] > 0)
            if (($orderProducts[$i]['temperature'] === "SEC") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['ambient']['products'][] = $orderProducts[$i];
                $products['ambient']['sumColis'] += $orderProducts[$i]['quantite'];
                $products['ambient']['sumPrice'] += $orderProducts[$i]['totalPrice'];
            }

            if (($orderProducts[$i]['temperature'] === "FRAIS") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['positiveCold']['products'][] = $orderProducts[$i];
                $products['positiveCold']['sumColis'] += $orderProducts[$i]['quantite'];
                $products['positiveCold']['sumPrice'] += $orderProducts[$i]['totalPrice'];
            }

            if (($orderProducts[$i]['temperature'] === "SURGELE") && ($orderProducts[$i]['quantite'] > 0)) {
                $products['negativeCold']['products'][] = $orderProducts[$i];
                $products['negativeCold']['sumColis'] += $orderProducts[$i]['quantite'];
                $products['negativeCold']['sumPrice'] += $orderProducts[$i]['totalPrice'];
            }
        }

        foreach ($productsByRayon as $productByRayon) {
            $listRayon[] = $productByRayon['fr'];
            for ($i = 0; $i < $totalItems; $i++) {
                if ($orderProducts[$i]['sf'] == $productByRayon['id']) {
                    $listProductsByRayon[] = $orderProducts[$i];
                }
            }
        }

        $dateSigned = null;
        $dateUpdated = null;

        if ($clientInfos['cgvCpvSignedAt'] != null) {
            $dateSigned = $clientInfos['cgvCpvSignedAt']->format('Y-m-d H:i:s');
        }

        if ($clientInfos['cgvCpvUpdatedAt'] != null) {
            $dateUpdated = $clientInfos['cgvCpvUpdatedAt']->format('Y-m-d H:i:s');
        }

        $zoneSec = $this->get('security.token_storage')->getToken()->getUser()->getZoneSec();
        $zoneFrais = $this->get('security.token_storage')->getToken()->getUser()->getZoneFrais();
        $zoneSurgele = $this->get('security.token_storage')->getToken()->getUser()->getZoneSurgele();

        $zoneDays = "zoneDays = {";
        if ($zoneSec !== NULL) {
            $zoneDays .= "SEC : [" . implode(", ", $zoneSec->getJoursOuverture()) . "],";
        } else {
            $zoneDays .= "SEC : [0, 1, 2, 3, 4, 5, 6],";
        }
        if ($zoneFrais !== NULL) {
            $zoneDays .= "FRAIS : [" . implode(", ", $zoneFrais->getJoursOuverture()) . "],";
        } else {
            $zoneDays .= "FRAIS : [0, 1, 2, 3, 4, 5, 6],";
        }
        if ($zoneSurgele !== NULL) {
            $zoneDays .= "SURGELE : [" . implode(", ", $zoneSurgele->getJoursOuverture()) . "]";
        } else {
            $zoneDays .= "SURGELE : [0, 1, 2, 3, 4, 5, 6],";
        }
        $zoneDays .= "}";


        $formType = new ResearchType();
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        $precommandeType = $this->getUser()->getPreCommande();

        $remainingValidationTime = null;
        if (null !== $precommandeType) {
            $entreprise = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => sprintf('%s%s', $this->get('sogedial.multisite')->getSociete(), $precommandeType)));

            // Retrieve next validation date an format it.
            $rawNextValidationDate = $this->get('sogedial_site.validationday')->getNextValidationDate($entreprise);
            $formattedNextValidationDate = $this->get('sogedial_integration.i18n')->frenchDate('dd m Y', $rawNextValidationDate);

            $rawNextDeliveryDate = $this->get('sogedial_site.validationday')->getNextDeliveryDate($entreprise);
            $formattedNextDeliveryDate = $this->get('sogedial_integration.i18n')->frenchDate('dd m Y', $rawNextDeliveryDate);

            // Retrieve remaining time until next order validation deadline.
            $remainingTimeToNextValidation = $this->get('sogedial_site.validationday')->getRemainingTimeToNextValidation($entreprise);
            $remainingValidationTime = [
                'days' => $remainingTimeToNextValidation->d,
                'hours' => $remainingTimeToNextValidation->h,
                'mins' => $remainingTimeToNextValidation->i,
            ];
        }

        // If the client is a prospect, retrieve his expiration date.
        $isProspect = $entityClient->isProspect();
        if ($isProspect) {
            $rawExpirationDate = $currentUser->getDateFinValidite();
            if (!is_null($rawExpirationDate)) {
                $formattedExpirationDate = $this->get('sogedial_integration.i18n')->frenchDate('d dd m Y', $rawExpirationDate);
                $remainingTimeToExpiration = $this->get('sogedial_site.validationday')->getRemainingTimeFromNow($rawExpirationDate);
                $remainingProspectTime = [
                    'years' => $remainingTimeToExpiration->y,
                    'months' => $remainingTimeToExpiration->m,
                    'days' => $remainingTimeToExpiration->d,
                    'hours' => $remainingTimeToExpiration->h,
                    'mins' => $remainingTimeToExpiration->i,
                ];
            }
        }

        $shippingMode = NULL;
        if ($precommandeType === 2) {
            $shippingMode = 'bateau';
        } elseif ($precommandeType === 1) {
            $shippingMode = 'avion';
        }

        // $shippingMode = ($precommandeType == 2 ? 'bateau' : 'avion');

        $commentaireCommandePending = $this->get('sogedial_integration.commande')->getCommentaireCommandePending($this->getUser()->getEntrepriseCourante());

        $poidsTotal = null;
        $volumeTotal = null;
        if ($societe === 'sogedial') {
            $totalVolumeWeight = $em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order["order"]["o_id"]);
            $poidsTotal = $totalVolumeWeight["poidsTotal"];
            $volumeTotal = $totalVolumeWeight["volumeTotal"];
        }

        $paramsView = array(
            "preCommandeMode" => $this->getUser()->getPreCommande() !== NULL,
            'is_prospect' => $isProspect,
            'listProductsByRayon' => $listProductsByRayon,
            'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
            'clientInfo' => $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId()),
            'entrepriseInfos' => $entrepriseInfos,
            'orderProducts' => $products,
            'client' => $codeClient,
            'orderNumber' => $order["order"]["o_numero"],
            'orderId' => $order["order"]["o_id"],
            'clientNameUrl' => $clientNameUrl,
            "commentaireCommandePending" => $commentaireCommandePending,
            'conditionsUpdatedAt' => $dateUpdated,
            'conditionsSignedAt' => $dateSigned,
            'clientInfos' => $clientInfos,
            'nbrProduct' => $totalItems,
            'listRayons' => $listRayon,
            'request' => $request,
            'conditionsContent' => $this->get('sogedial_integration.catalogue')->getUserCgcCpv(),
            'baseUrl' => $this->container->getParameter('baseUrl'),
            'zoneDays' => $zoneDays,
            'arrayHoliday' => $em->getRepository('SogedialSiteBundle:JoursFeries')->getJourFerieByCodeSociete($multisite->getSociete()),
            'dateLivraisonEstimee' => (isset($rawNextDeliveryDate) ? $rawNextDeliveryDate : null),
            'formattedNextValidationDate' => (isset($formattedNextValidationDate) ? $formattedNextValidationDate : null),
            'formattedNextDeliveryDate' => (isset($formattedNextDeliveryDate) ? $formattedNextDeliveryDate : null),
            'formattedExpirationDate' => ($isProspect && $rawExpirationDate ? $formattedExpirationDate : null),
            'remainingValidationTime' => $remainingValidationTime,
            'remainingProspectTime' => ($isProspect && $rawExpirationDate ? $remainingProspectTime : null),
            'shippingMode' => $shippingMode,
            'societe' => $societe,
            'region' => $this->get('sogedial.multisite')->getRegion(),
            "features" => array_flip($multisite->getFeatures()),
            'poidsTotal' => $poidsTotal,
            'volumeTotal' => $volumeTotal,
            'isEactif' => $isEactif
        );

        $paramsView['form'] = $form->createView();

        return $this->render('SogedialIntegrationBundle:Commande:panier.html.twig', $paramsView);
    }

    /**
     * @param Request $request
     * @param Commande $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function detailHistoriqueCommandeAction(Request $request, $societe, Commande $order)
    {
        $response = new Response();
        $response->setMaxAge(30);

        if (!$response->isNotModified($request)) {
            $em = $this->getDoctrine()->getManager();
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());
            $currentUser = $this->getUser();
            $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $currentUser->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));

            $orderClient = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $order->getUser()->getMeta()->getCode(), "entreprise" => $order->getEntreprise()));
            if ($order->getUser() !== NULL && $order->getUser()->getEtat() === 'client') {
                if ($order->getCodePrecommande() === NULL) {
                    $orderClient = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $order->getUser()->getMeta()->getCode(), "entreprise" => $order->getEntreprise()->getCode()));
                } else {
                    $orderClient = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $order->getUser()->getMeta()->getCode(), "entreprise" => $order->getCodePrecommande()->getCode()));
                }
                $displayName = $orderClient->getNom();
            }

            if ($order->getValidator() !== NULL && $order->getValidator()->getEtat() == 'client') {
                $displayName = $orderClient->getNom();
            } elseif ($order->getValidator() !== NULL && $order->getValidator()->getEtat() !== 'client') {
                $displayName = $order->getValidator()->getNom() . ' ' . $order->getValidator()->getPrenom();
            }

            if ($order->getCodePrecommande() === NULL) {
                if (($client->getCode() !== $orderClient->getCode()) || ($client->getEntreprise()->getCode() !== $order->getEntreprise()->getCode())) {
                    return new RedirectResponse($this->get('router')->generate('sogedial_integration_dashbord', array('societe' => $societe)));
                }
            } else {
                if (($client->getCode() !== $orderClient->getCode()) || ($client->getEntreprise()->getCode() !== $order->getCodePrecommande()->getCode())) {
                    return new RedirectResponse($this->get('router')->generate('sogedial_integration_dashbord', array('societe' => $societe)));
                }
            }

            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $stockColis = $multiplyByPcb;

            $listProductsByRayon = array();
            $listRayon = array();
            $currentUser = $this->getUser();
            $em = $this->getDoctrine()->getManager();
            $currentOrder = $em->getRepository('SogedialSiteBundle:Commande')->getCurrentOrderByUser($currentUser->getId(), $currentUser->getEntrepriseCourante());
            if (null == $order) {
                return $this->redirect($this->generateUrl('SogedialSite_catalogue_integration', array('societe' => $societe)));
            }

            $orderProducts = $em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($order->getId(), $order->getTemperatureCommande(), $multiplyByPcb, $stockColis);
            $totalResult = (int)count($orderProducts['result']);

            $orderTotalAmount = 0;
            for ($i = 0; $i < $totalResult; $i++) {
                $orderTotalAmount += $orderProducts['result'][$i]['totalPrice'];
            }

            $historyOrderProducts = array();
            if (null !== $currentUser->getPrecommande()) {
                $historyOrderProducts = $this->get('sogedial.historique_ligne_commande')->getHistoriqueLigneCommandeByCommandeId($order->getParent());
            }

            foreach ($orderProducts['tree'] as $productByRayon) {
                $listRayon[] = $productByRayon['fr'];
                for ($i = 0; $i < $totalResult; $i++) {
                    if ($orderProducts['result'][$i]['sf'] == $productByRayon['id']) {
                        if (array_key_exists($orderProducts['result'][$i]["ligneCommandId"], $historyOrderProducts)) {
                            $orderProducts['result'][$i]["history"] = $historyOrderProducts[$orderProducts['result'][$i]["ligneCommandId"]];
                        } else {
                            $orderProducts['result'][$i]["history"] = [];
                        }

                        $listProductsByRayon[] = $orderProducts['result'][$i];
                    }
                }
            }

            $poidsTotal = null;
            $volumeTotal = null;
            if ($societe === 'sogedial') {
                $totalVolumeWeight = $em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order->getId());
                $poidsTotal = $totalVolumeWeight["poidsTotal"] ? $totalVolumeWeight["poidsTotal"] : $order->getPoidsCommande();
                $volumeTotal = $totalVolumeWeight["volumeTotal"] ? $totalVolumeWeight["volumeTotal"] : $order->getVolumeCommande();
            }

            $bonPreparations = $em->getRepository('SogedialSiteBundle:BonPreparation')->findBy(array("commande" => $order->getId()));
            $bonPreparationsTotal = [];
            $bonPreparationsTotal["totalColisFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumColisFacturation($order->getId());
            $bonPreparationsTotal["totalMontantFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumMontantFacturation($order->getId());

            $formType = new ResearchType();
            $form = $this->createForm($formType);
            $form->handleRequest($request);

            // Retrieve order status
            $orderStatusLibelle = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneByOrder($order)->getOrderStatus()->getLibelle();
            $orderStatusKey = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneByOrder($order)->getOrderStatus()->getLibelle();

            $paramViews = array(
                'order' => $order,
                'request' => $request,
                'displayName' => $displayName,
                "bonPreparations" => $bonPreparations,
                "bonPreparationsTotal" => $bonPreparationsTotal,
                'listProductsByRayon' => $listProductsByRayon,
                'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
                'nbrProduct' => $totalResult,
                'listRayons' => $listRayon,
                'clientInfo' => $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId()),
                'entreprise' => $this->get('sogedial.multisite')->getMasterEnterprise(),
                'orderTotalAmount' => $orderTotalAmount,
                'orderNumber' => $order->getNumero(),
                'orderId' => $order->getId(),
                'state' => ($currentOrder === null) ? 0 : 1,
                'baseUrl' => $this->container->getParameter('baseUrl'),
                'commentaire' => $order->getCommentaire(),
                'listSociete' => $this->get('sogedial_integration.catalogue')->getListSocieteByCodeRegionAndCodeClient($clientInfo['code']),
                "preCommandeMode" => $this->getUser()->getPreCommande() !== NULL,
                'societe' => $societe,
                'region' => $this->get('sogedial.multisite')->getRegion(),
                'orderStatusLibelle' => $orderStatusLibelle,
                'orderStatut' => $orderStatusKey,
                'volumeTotal' => $volumeTotal,
                'poidsTotal' => $poidsTotal
            );

            $paramViews['form'] = $form->createView();

            // Create breadcrumb nodes
            $breadcrumbs = $this->get("white_october_breadcrumbs");
            if ($this->getUser()->getPreCommande() == NULL) {
                $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_dashbord', ['societe' => $societe]);
            }
            $breadcrumbs->addRouteItem('Historique des commandes', 'SogedialSite_integration_pending_orders', ['societe' => $societe]);
            $breadcrumbs->addItem('Détail de la commande n°' . $paramViews['orderNumber']);

            $response->setContent($this->renderView('SogedialIntegrationBundle:Commande:detail-historique-commande.html.twig', $paramViews));
        }

        return $response;

    }

    /**
     * @param Request $request
     * @return Response
     */
    public function commandeAction(Request $request)
    {
        $paramViews = array();
        return $this->render('SogedialIntegrationBundle:Commande:commande.html.twig', $paramViews);
    }

    /**
     * @param Request $request
     * @param $societe
     * @return Response
     */
    public function historiqueCommandeAction(Request $request, $societe)
    {
        $response = new Response();
        $response->setMaxAge(300);

        if (!$response->isNotModified($request)) {
            $em = $this->getDoctrine()->getManager();
            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());

            $formType = new ResearchType();
            $form = $this->createForm($formType);
            $form->handleRequest($request);

            // Create breadcrumb nodes
            $breadcrumbs = $this->get("white_october_breadcrumbs");
            if ($this->getUser()->getPreCommande() == NULL) {
                $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_dashbord', ['societe' => $societe]);
            }
            $breadcrumbs->addItem('Historique des commandes');

            $paramViews = array(
                'entreprise' => $this->get('sogedial.multisite')->getMasterEnterprise(),
                'orders' => $this->get('sogedial_integration.catalogue')->getOrdersByUser($multiplyByPcb, null),
                'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
                'clientInfo' => $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId()),
                'listSociete' => $this->get('sogedial_integration.catalogue')->getListSocieteByCodeRegionAndCodeClient($clientInfo['code']),
                'preCommandeMode' => $this->getUser()->getPreCommande() !== NULL,
                'request' => $request,
                'societe' => $societe
            );

            $paramViews['form'] = $form->createView();

            $response->setContent($this->renderView('SogedialIntegrationBundle:Commande:commande-encours.html.twig', $paramViews));
        }

        return $response;

    }

    /**
     * @param Request $request
     * @param Commande $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function orderRenewAction(Request $request, $societe, Commande $order)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($user->getEntrepriseCourante());

        if (!$this->get("sogedial_integration.commande")->orderRenew($order)) {
            //@TODO: stop - gerer le cas d'erreur si le renouvellement az raté
        }

        if ($this->getUser()->getPreCommande() !== NULL) {
            return $this->redirect($this->generateUrl('sogedial_integration_catalogue', array('societe' => $entrepriseObj->getNomEnvironnement())));
        } else {
            return $this->redirect($this->generateUrl('sogedial_integration_dashbord', array('societe' => $entrepriseObj->getNomEnvironnement())));
        }
    }

    /**
     * @param $comment
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function commentCommandeAction($comment)
    {
        $commentArray = explode('-', $comment);
        $commentString = join(' ', $commentArray);
        $this->get('sogedial_integration.commande')->setCommentaireCommandePending($commentString);


        $commentaire = $this->get('sogedial_integration.commande')->getCommentaireCommandePending();
        return new Response(
            '<html><body>' . $commentaire . '</body></html>'
        );
    }

    private function checkDateLivraison($dateLivraison)
    {
        $patternDate = "[0-3][0-9][01][0-9]201[1-9]{2}";
        $ambiant = "$patternDate,($patternDate){0,1},($patternDate){0,1}";
        $negatif = "($patternDate){0,1},$patternDate,($patternDate){0,1}";
        $positif = "($patternDate){0,1},($patternDate){0,1},$patternDate";
        $patternDateLivraison = "/^($ambiant|$negatif|$positif)$/";

        return 1 === preg_match($patternDateLivraison, $dateLivraison);
    }

    private function checkComment($comment)
    {
        return strlen($comment) <= 40;
    }


    private function checkValidateCommande($dateLivraison, $comment)
    {
        return $this->checkDateLivraison($dateLivraison) && $this->checkComment($comment);
    }

    /**
     * @param Commande $order
     * @param string $dateLivraison
     * @param $comment
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function validateCommandeAction(Commande $order, $dateLivraison, $comment)
    {
        if (!$this->checkValidateCommande($dateLivraison, $comment)) {
            //@todo: gerer les erreurs de validate commande
        }

        $currentUser = $this->getUser();
        if ($order->getUser()->getId() === $currentUser->getId()) {
            $entrepriseObj = $this->getDoctrine()->getManager()->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());

            $validatingDate = $this->get('sogedial_integration.commande')->validateCommandeOrder(
                $order,
                $currentUser,
                $dateLivraison,
                $comment
            );

            $this->get('order_validation_subscriber')->addPanierId($order->getId());

            if ($currentUser->getPreCommande() !== NULL) {
                $this->get('session')->getFlashBag()->add(
                    'success_validate_precommande',
                    "Votre commande a été enregistrée, elle sera traitée bientôt.");
                    //"Votre commande a été enregistrée, elle sera traitée le " . $this->get('sogedial_integration.i18n')->frenchDate("d dd m", $validatingDate) . "."); // TODO : get this information from the database after migrating @Sictoz
                return $this->redirect($this->generateUrl('sogedial_integration_catalogue', array('societe' => $entrepriseObj->getNomEnvironnement())));
            } else {
                $this->get('session')->getFlashBag()->add('success_validate_precommande', "Votre commande a été enregistrée.");
                return $this->redirect($this->generateUrl('sogedial_integration_dashbord', array('societe' => $entrepriseObj->getNomEnvironnement())));
            }
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCurrentOrderAction(Request $request)
    {
        $currentUser = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $orderRepository = $em->getRepository('SogedialSiteBundle:Commande');
        $orderProductRepository = $em->getRepository('SogedialSiteBundle:LigneCommande');
        $currentOrder = $orderRepository->getCurrentOrderByUser($currentUser, $currentUser->getEntrepriseCourante());

        $currentOrdersProduct = $orderProductRepository->findByCommande($currentOrder);

        foreach ($currentOrdersProduct as $currentOrderProduct) {
            if ($currentOrder instanceof Commande) {
                $orderId = $currentOrder->getId();
                $orderProductRepository->clearLigneCommande($orderId);
            }
        }

        return new JsonResponse(
            array(
                'message' => 'true'
            )
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteCurrentOrderAction(Request $request)
    {
        $currentUser = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $orderRepository = $em->getRepository('SogedialSiteBundle:Commande');
        $orderOrderStatusRepository = $em->getRepository('SogedialSiteBundle:OrderOrderStatus');
        $orderProductRepository = $em->getRepository('SogedialSiteBundle:LigneCommande');
        $currentOrder = $orderRepository->getCurrentOrderByUser($currentUser, $currentUser->getEntrepriseCourante());

        $currentOrdersProduct = $orderProductRepository->findByCommande($currentOrder);

        foreach ($currentOrdersProduct as $currentOrderProduct) {
            $em->remove($currentOrderProduct);
        }

        $em->flush();

        if ($currentOrder instanceof Commande) {
            $orderId = $currentOrder->getId();
            $orderOrderObject = $orderOrderStatusRepository->findOneBy(array('order' => $orderId));
            $orderStatusObject = $em->getRepository('SogedialSiteBundle:OrderStatus')->findOneByKey('STATUS_DELETED');

            if ($orderOrderObject instanceof OrderOrderStatus) {
                $orderOrderObject->setOrderStatus($orderStatusObject);
                $em->persist($orderOrderObject);
                $em->flush();
            }

        }
        $currentUser = $this->getUser();
        $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());

        return $this->redirect($this->generateUrl('sogedial_integration_dashbord', array("societe" => $entrepriseObj->getNomEnvironnement())));
    }

    /**
     * @ParamConverter("produit", class="SogedialSiteBundle:Produit")
     * @return type
     */
    public function removeProductToCurrentOrderAction(Produit $product, Request $request)
    {
        $currentUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $em->getRepository('SogedialSiteBundle:Commande')->getCurrentOrderByUser($currentUser, $currentUser->getEntrepriseCourante())->setUpdatedAt(new \DateTime());
        $currentOrderProduct = $em->getRepository('SogedialSiteBundle:LigneCommande')->getCurrentOrderProductByUserAndProduct($currentUser, $currentUser->getEntrepriseCourante(), $product);

        if ($currentOrderProduct === null) {
            return new JsonResponse(
                array(
                    'message' => 'false'
                )
            );
        }
        $em->remove($currentOrderProduct);
        $em->flush();

        return new JsonResponse(
            array(
                'message' => 'true'
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentOrderWeightVolumeAction(Request $request)
    {
        $result = $this->get('sogedial_integration.catalogue')->getCommande();

        if (!(array_key_exists('poidsNegativeCold', $result))) {
            $result['poidsNegativeCold'] = 0;
            $result['volumeNegativeCold'] = 0;
        }

        if (!(array_key_exists('poidsPositiveCold', $result))) {
            $result['poidsPositiveCold'] = 0;
            $result['volumePositiveCold'] = 0;
        }

        if (!(array_key_exists('poidsAmbient', $result))) {
            $result['poidsAmbient'] = 0;
            $result['volumeAmbient'] = 0;
        }

        return new JsonResponse(
            array(
                'poidsTotal' => $result['poidsTotal'],
                'volumeTotal' => $result['volumeTotal'],
                'poidsNegativeCold' => $result['poidsNegativeCold'],
                'volumeNegativeCold' => $result['volumeNegativeCold'],
                'poidsPositiveCold' => $result['poidsPositiveCold'],
                'volumePositiveCold' => $result['volumePositiveCold'],
                'poidsAmbient' => $result['poidsAmbient'],
                'volumeAmbient' => $result['volumeAmbient']
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function editProductToCurrentOrderAction(Request $request)
    {
        $this->check($request);

        $this->get('sogedial.panier')->handleBracketChanges(
            $this->getUser(),
            $request->get('references')
        );

        return new JsonResponse(
            array(
                'title' => $this->translate('product.order.add'),
                'message' => $this->translate('order.quantity')
            )
        );
    }

    private function checkRequest(Request $request)
    {
        return !($request === NULL || $request->get("references") === NULL);
    }

    private function displayBadRequest()
    {
        throw new BadRequestHttpException(
            'La requete doit etre de la forme reference : {<code-produit> : <quantite>}'
        );
    }

    private function checkQuantite($references)
    {
        foreach ($references as $product => $quantity) {
            if (!(is_numeric($quantity) && is_int(intval($quantity)))) {
                return $quantity;
            }
        }

        return true;
    }

    private function displayBadQuantite($quantity)
    {
        throw new BadRequestHttpException(
            "La quantite de chaque produit doit etre un entier: \"$quantity\" reçu."
        );
    }

    private function check(Request $request)
    {
        if (!$this->checkRequest($request)) {
            return $this->displayBadRequest();
        }

        $checkQuantity = $this->checkQuantite($request->get('references'));
        if ($checkQuantity !== true) {
            return $this->displayBadQuantite($checkQuantity);
        }
        return False;
    }

    private function translate($key)
    {
        $translator = $this->get('translator');
        return $translator->trans($key, array(), 'SogedialSiteBundle');
    }

    /**
     * @param Request $request
     * @param Commande $commande
     * @return mixed
     */
    public function recapOrderPdfAction(Request $request, Commande $commande)
    {
        return $this->get('sogedial.export')->toPdfRecapExport($commande);
    }

}
