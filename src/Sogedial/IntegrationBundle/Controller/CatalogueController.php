<?php

namespace Sogedial\IntegrationBundle\Controller;

use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Rayon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sogedial\IntegrationBundle\Form\Type\ResearchType;
use Symfony\Component\HttpFoundation\JsonResponse;

class CatalogueController extends Controller
{

    public function indexCatalogueAction()
    {
        return $this->render('SogedialIntegrationBundle:Catalogue:index.html.twig');
    }

    /**
     * @param $listProductsByRayon
     * @param $listRayons
     * @return Response
     */
    public function tableCatalogueAction($listProductsByRayon, $listRayons, $lastTitle = '')
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());

        $paramViews = array(
            "preCommandeMode" => $this->getUser()->getPreCommande() !== NULL,
            'listProductsByRayon' => $listProductsByRayon,
            'listRayons' => $listRayons,
            'baseUrl' => $this->container->getParameter('baseUrl'),
            'lastTitle' => $lastTitle,
            'currentPage' => 'catalogue',
            'societe' => $entrepriseObj->getNomEnvironnement(),
            'region' => $this->get('sogedial.multisite')->getRegion(),
            'clientInfo' => $em->getRepository('SogedialUserBundle:User')->getClientInformation($currentUser->getId())
        );

        return $this->render('SogedialIntegrationBundle:Common:table-universal.html.twig', $paramViews);
    }

    /**
     * If called, renders the qmin value of a given product for the current user.
     * @param $product
     * @return Response
     */
    public function productQMinAction($product)
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($currentUser->getId());
        $codeClient = $clientInfo['code'];
        $codeProduit = $product['code'];
        $qMinArray = $em->getRepository('SogedialSiteBundle:ClientProduitMOQ')->getQMinFromCodeClientAndCodeProduit($codeClient, $codeProduit);

        if (!is_null($qMinArray)) {
            $qMin = $qMinArray['quantiteMinimale'];
        } else {
            $qMin = null;
        }

        $paramViews = array(
            "qMin" => $qMin,
        );

        return $this->render('SogedialIntegrationBundle:Catalogue:product-qmin.html.twig', $paramViews);
    }

    public function sidebarInfiniteAction(Request $request)
    {
        $response = new Response();
        $response->setMaxAge(1200);

        if (!$response->isNotModified($request)) {
            $em = $this->getDoctrine()->getManager();
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());
            $user = $this->getUser();
            $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($user->getEntrepriseCourante());
            $listSociete = array();

            if($user->getPreCommande() === NULL){
                $listSociete = $this->get('sogedial_integration.catalogue')->getListSocieteByCodeRegionAndCodeClient($clientInfo['code']);
            } else {
                $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $user->getMeta()->getCode(), "entreprise" => $user->getEntrepriseCourante()));
                $entrepriseParentCode = $client->getEntreprise()->getEntrepriseParent()->getCode();
                $entreprisesEnfants = $em->getRepository('SogedialSiteBundle:Entreprise')->findBy(array('entrepriseParent' => $entrepriseParentCode));

                foreach($entreprisesEnfants as $key => $value){
                    $raisonSocialsWords = explode(' ', $value->getRaisonSociale());
                    $transportingLabel = array_pop($raisonSocialsWords);

                    $listSociete []= array($transportingLabel, NULL, 1, $value->getCode());
                }
            }

            $codeSociete = $this->get('sogedial.multisite')->getSociete();

            $paramViews = array(
                "preCommandeMode" => $this->getUser()->getPreCommande() !== NULL,
                'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($codeSociete),
                'families' => $this->get('sogedial_integration.catalogue')->getSidebarElement($this->getUser()),
                'codeSecteur' => $request->query->get('codeSecteur'),
                'codeRayon' => $request->query->get('codeRayon'),
                'codeFamille' => $request->query->get('codeFamille'),
                'entreprise' => $entrepriseObj->getRaisonSociale(),
                'listSociete' => $listSociete,
                'societe' => $entrepriseObj->getNomEnvironnement(),
                'currentAssortiment' => $this->get('sogedial_integration.catalogue')->getCurrentAssortimentByCodeClient(sprintf('%s-%s',$codeSociete,$this->getUser()->getUsername()))
            );

            $response->setContent($this->renderView('SogedialIntegrationBundle:Catalogue:aside-infinite.html.twig', $paramViews));
        }

        return $response;
    }

    public function basketAction()
    {
        $result = $this->get('sogedial_integration.catalogue')->getCommande();

        $paramViews = array(
            'order' => $result['order'],
            'totalAmount' => $result['totalAmount'],
            'poidsTotal' => $result['poidsTotal'],
            'volumeTotal' => $result['volumeTotal']
        );

        return $this->render('SogedialIntegrationBundle:Catalogue:aside.html.twig', $paramViews);
    }

    function looksLikeEAN($search)
    {
        if (!is_string($search)) {
            return false;
        }
        $trimmed = trim($search);
        $length = strlen($trimmed);
        if ($length!==8 && $length !==13 && $length !==15 && $length !==18)   // autorisés : EAN-8 ; EAN-13 ; EAN-13 + EAN-2 ; EAN-13 + EAN-5
        {
            return false;
        }
        return ctype_digit($trimmed);   // tous les caractères sont des chiffres
    }

    /**
     * @param Request $request
     * @param $societe
     * @param $page
     * @param $tri
     * @param $ordertri
     * @return Response
     */
    public function indexAction(Request $request, $societe, $page, $tri, $ordertri)
    {
        $em = $this->getDoctrine()->getManager();
        $societeCC = false;

        if($request->query->get('_societe_cc') && $request->query->get('_societe_cc') != ''){
            $societeCC = $request->query->get('_societe_cc');
        }

        $this->get('sogedial.multisite')->initSessionUser($this->get('security.token_storage')->getToken(), $societeCC);

        $currentUser = $this->getUser();
        $entityClient = $em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $currentUser->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));

        // Pour fixer le problème de redirection avec le referer
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $paramViews = $this->get('sogedial_integration.catalogue')->loadProductPage($request, $societe,1, $tri, $ordertri);
        // redirect vers une page produit en cas du résultat unique dont
        // le code EAN-13 matche parfaitement la requête
        $search = ($request->query->get('produits')) ? $request->query->get('produits') : '';
        if( $paramViews['totalProduct'] == 1){
            if($search == $paramViews['products'][0]['ean13']){
                $x = $this->forward('SogedialIntegrationBundle:Produit:ficheProduit', array('societe' => $societe,'code' => $paramViews['products'][0]['code']));   // it saves 400ms, but keeps the original URL
                return $x;
            }
        }

        $formType = new ResearchType();
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        $paramViews['families'] = $this->get('sogedial_integration.catalogue')->getSidebarElement($this->getUser());
        $paramViews['form'] = $form->createView();
        $paramViews["search"] = $search;
        $paramViews['lastTitle'] = '';
        $paramViews["preCommandeMode"] = $this->getUser()->getPreCommande() !== NULL;
        $paramViews["societe"] = $societe;
        $paramViews['region'] = $this->get('sogedial.multisite')->getRegion();
        $paramViews['client'] = $this->get("session")->get('code_client');
        $paramViews['clientInfo'] = $em->getRepository('SogedialUserBundle:User')->getClientInformation($currentUser->getId());

        if (($paramViews['totalProduct'] == 0) && $this->looksLikeEAN($paramViews["search"])) {
            $paramViews["search"] = "";
        }

        $entreprise = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $this->get('sogedial.multisite')->getSociete()));

        // Retrieve next validation date an format it.
        if($this->getUser()->getPreCommande() !== NULL) {
            $rawNextValidationDate = $this->get('sogedial_site.validationday')->getNextValidationDate($entreprise);
            $paramViews['formattedNextValidationDate'] = $this->get('sogedial_integration.i18n')->frenchDate('dd m Y', $rawNextValidationDate);

            $rawNextDeliveryDate = $this->get('sogedial_site.validationday')->getNextDeliveryDate($entreprise);
            $paramViews['formattedNextDeliveryDate'] = $this->get('sogedial_integration.i18n')->frenchDate('dd m Y', $rawNextDeliveryDate);
            // Retrieve remaining time until next order validation deadline.
            $remainingTimeToNextValidation = $this->get('sogedial_site.validationday')->getRemainingTimeToNextValidation($entreprise);
            $paramViews['remainingValidationTime'] = [
                'days' => $remainingTimeToNextValidation->d,
                'hours' => $remainingTimeToNextValidation->h,
                'mins' => $remainingTimeToNextValidation->i,
            ];
        }

        // If the client is a prospect, retrieve his expiration date.
        $isProspect = $entityClient->isProspect();
        $paramViews['is_prospect'] = $isProspect;
        if ($isProspect) {
            $rawExpirationDate = $currentUser->getDateFinValidite();
            if (!is_null($rawExpirationDate)) {
                $paramViews['formattedExpirationDate'] = $this->get('sogedial_integration.i18n')->frenchDate('d dd m Y', $rawExpirationDate);
                $remainingTimeToExpiration = $this->get('sogedial_site.validationday')->getRemainingTimeFromNow($rawExpirationDate);
                $paramViews['remainingProspectTime'] = [
                    'years' => $remainingTimeToExpiration->y,
                    'months' => $remainingTimeToExpiration->m,
                    'days' => $remainingTimeToExpiration->d,
                    'hours' => $remainingTimeToExpiration->h,
                    'mins' => $remainingTimeToExpiration->i,
                ];
            }
        }

        $precommandeType = $this->getUser()->getPreCommande();
        $shippingMode = NULL;
        if($precommandeType === 2){
            $shippingMode = 'bateau';
        } elseif($precommandeType === 1){
            $shippingMode = 'avion';
        }

        $paramViews['shippingMode'] = $shippingMode;
        // Prepare parameters for links/routing in the breadcrumbs
        $catalogueBaseRoute = 'sogedial_integration_catalogue';
        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        if ($this->getUser()->getPreCommande() == NULL) {
            $breadcrumbs->addRouteItem("Dashboard", "sogedial_integration_dashbord", [
            'societe' => $societe,
            ]);
        }

        $breadcrumbs->addRouteItem('Catalogue produits', $catalogueBaseRoute, [
            'societe' => $societe]);

        $kind = $request->query->get('kind');
        switch ($kind) {
            case 'new':
                $breadcrumbs->addItem('Nouveautés');
                break;
            case 'promotion':
                $breadcrumbs->addItem('Promotions');
                break;
            default:
                $codeSecteur = $request->query->get('codeSecteur');
                if ($codeSecteur) {
                    $nomSecteur = $em->getRepository('SogedialSiteBundle:Secteur')->findOneBy(array('code' => $codeSecteur))->getLibelle();
                    $secteurParameters = [
                        'societe' => $societe,
                        'codeSecteur' => $codeSecteur];
                    $breadcrumbs->addRouteItem($nomSecteur, $catalogueBaseRoute, $secteurParameters);

                    $codeRayon = $request->query->get('codeRayon');
                    if ($codeRayon) {
                        $nomRayon = $em->getRepository('SogedialSiteBundle:Rayon')->findOneBy(array('code' => $codeRayon))->getLibelle();
                        $rayonParameters = [
                            'societe' => $societe,
                            'codeSecteur' => $codeSecteur,
                            'codeRayon' => $codeRayon
                        ];
                        $breadcrumbs->addRouteItem($nomRayon, $catalogueBaseRoute, $rayonParameters);

                        $codeFamille = $request->query->get('codeFamille');
                        if ($codeFamille) {
                            $nomFamille = $em->getRepository('SogedialSiteBundle:Famille')->findOneBy(array('code' => $codeFamille))->getLibelle();
                            $familleParameters = [
                                'societe' => $societe,
                                'codeSecteur' => $codeSecteur,
                                'codeRayon' => $codeRayon,
                                'codeFamille' => $codeFamille
                            ];
                            $breadcrumbs->addRouteItem($nomFamille, $catalogueBaseRoute, $familleParameters);
                        }
                    }
                }
                break;
        }

        return $this->render('SogedialIntegrationBundle:Catalogue:index-infinite.html.twig', $paramViews);
    }

    /**
    * @param Request $request
    */
    public function updateMOQClientAction(Request $request){
        $em = $this->getDoctrine()->getManager();

        $codeProduit = $request->request->get('code_produit');
        $codeClient = $request->request->get('code_client');
        $quantity = $request->request->get('moq_client_demande');

        $product = $em->getRepository('SogedialSiteBundle:Produit')
                        ->getProduitByCode($codeProduit);
        $client = $em->getRepository('SogedialSiteBundle:Client')
                        ->getClientByCode($codeClient);

        $this->get("sogedial.client_produit_moq")
                ->appendOrUpdate($product, $client, $quantity);

        return new JsonResponse(array(
            'update' => true
        ));

    }

    /**
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function productLoadAction(Request $request, $societe, $page, $tri, $ordertri, $lastTitle)
    {
        $paramViews = $this->get('sogedial_integration.catalogue')->loadProductPage($request, $societe, $page, $tri, $ordertri);
        $paramViews['lastTitle'] = $lastTitle;
        $paramViews['currentPage'] = 'catalogue';
        $paramViews["preCommandeMode"] = $this->getUser()->getPreCommande() !== NULL;
        $paramViews["region"] = $this->get('sogedial.multisite')->getRegion();

        return $this->render('SogedialIntegrationBundle:Common:table-universal.html.twig', $paramViews);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function obtenirSuggestionsAction(Request $request)
    {
        $query = array();
        $query['search'] = $request->get('q');
        $searchService = $this->get('sogedial.recherche');

        // le véritable traitement est effectué dans le service
        $suggestions = array(
            'query' => $query['search'],
            'items' => $searchService->getSuggestions($query)
        );

        return new JsonResponse($suggestions);
    }

}