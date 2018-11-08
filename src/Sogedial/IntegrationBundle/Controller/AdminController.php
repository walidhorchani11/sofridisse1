<?php

namespace Sogedial\IntegrationBundle\Controller;

use Sogedial\UserBundle\Form\Type\SetupProspectOptionsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\Common\Collections\ArrayCollection;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\AssortimentClient;
use Sogedial\SiteBundle\Entity\MetaClient;
use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Photo;
use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Form\ProduitType;
use Sogedial\SiteBundle\Form\ProduitTypeLangue;
use Sogedial\SiteBundle\Form\ProduitTypeLangueEdit;
use Sogedial\SiteBundle\Form\ProduitImageType;
use Sogedial\SiteBundle\Form\ProduitFileType;
use Sogedial\SiteBundle\Form\Type\UploadPhotoType;
use Sogedial\SiteBundle\Form\Type\AddZoneAccessType;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\MessageClient;
use Sogedial\IntegrationBundle\Form\Type\UploadPdfType;
use Sogedial\UserBundle\Form\Type\AddClientProspectStep1Type;
use Sogedial\UserBundle\Form\Type\AddClientProspectStep2Type;
use Sogedial\UserBundle\Form\Type\AddClientProspectStep3Type;
use Sogedial\UserBundle\Entity\User;
use Sogedial\UserBundle\Form\Type\UserType;
use Sogedial\UserBundle\Form\Type\UserTypeClient;
use Sogedial\UserBundle\Form\Type\UserEditType;
use Sogedial\UserBundle\Form\Type\UserEditStatusType;
use Sogedial\UserBundle\Form\Type\UserEditSelectionType;
use Sogedial\UserBundle\Form\Type\AddClientAccessType;
use Sogedial\UserBundle\Form\Type\CredentialsType;
use Sogedial\UserBundle\Form\Type\GeolocationType;
use Sogedial\UserBundle\Form\Type\AddClientEnseigneAndAssortimentType;
use Sogedial\UserBundle\Form\Type\SetupClientOptionsType;
use Sogedial\UserBundle\Entity\FamilySelection;
use Sogedial\UserBundle\Entity\ProductSelection;

class AdminController extends Controller
{
    public function assortimentsClientAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        //$breadcrumbs = $this->breadcrumbAssortimentClient($id);
        $code_client = $id;
        $clientObject = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array('code' => $code_client));
        $assortiments = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findBy(array("client" => $clientObject->getCode()));

        $clientUser = $em->getRepository('SogedialSiteBundle:Client')->getUserFromClientId($id);

        if ($clientUser !== NULL) {
            $id = $clientUser->getId();
            $entreprise = $this->get('sogedial.multisite')->getSociete();
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($id);
            $societeObject = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($entreprise);
            $societeLabelle = $societeObject->getNomEnvironnement();
        } else {
            //error
        }

        $paramsViews = array(
            "code_client" => $code_client,
            "assortiments" => $assortiments,
            'commercialInfo' => $this->commercialInfo(),
            'clientInfo' => $clientInfo,
            'societe' => $societeLabelle,
        );

        return $this->render('SogedialIntegrationBundle:Admin/assortimentsclients:list.html.twig', $paramsViews);
    }

    private function breadcrumbAssortimentClient($codeClient)
    {
        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addRouteItem('Mes clients', 'sogedial_integration_admin_mesclients');
        // $breadcrumbs->addRouteItem($codeClient, 'sogedial_integration_admin_client', ['id' => $codeClient]);
        $breadcrumbs->addRouteItem('Assortiments', 'sogedial_integration_admin_client_assortiments', ['id' => $codeClient]);

        return $breadcrumbs;
    }

    private function commercialInfo()
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;

        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        if ($code_entreprise == false) {
            $code_entreprise = $this->get('sogedial.multisite')->getSociete();
        }

        return $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($code_entreprise);
    }

    public function assortimentsClientNewAction($id)
    {
        $paramsViews = array(
            'commercialInfo' => $this->commercialInfo(),
            "code_client" => $id,
            "mode" => "new"
        );

        $breadcrumbs = $this->breadcrumbAssortimentClient($id);
        $breadcrumbs->addItem('Création assortiment');

        return $this->render('SogedialIntegrationBundle:Admin/assortimentsclients:new.html.twig', $paramsViews);
    }

    public function assortimentsClientEditAction($id, $valeur)
    {
        $em = $this->getDoctrine()->getManager();
        $multiSiteService = $this->get('sogedial.multisite');
        $clientRepository = $em->getRepository('SogedialSiteBundle:Client');
        $hasTarificationFeature = $multiSiteService->hasFeature('tarifs-tarification');
        $client = $clientRepository->find($id);

        $breadcrumbs = $this->breadcrumbAssortimentClient($id);
        $breadcrumbs->addItem('Modification assortiment');

        if ($hasTarificationFeature) {
            $clientNewTarificationOrEnseigne = $client->getTarification();
            $clientOldTarificationOrEnseigne = $client->getTarification();
        } else {
            $clientNewTarificationOrEnseigne = $client->getEnseigne();
            $clientOldTarificationOrEnseigne = $client->getEnseigne();
        }
        // If the prospect's tarification or enseigne have been edited, force the generation of a new assortiment.
        if ($clientOldTarificationOrEnseigne->getCode() !== $clientNewTarificationOrEnseigne->getCode()) {
            $clientOldAssortiment = $client->getAssortiment();
            $clientOldAssortimentValeur = $clientOldAssortiment->getValeur();
            $clientCodeEntreprise = $client->getEntreprise()->getCode();
            $client->setAssortiment(null);
            // We must persist the modifications before deleting the old assortiment, otherwise the foreign key checks will throw an error.
            $em->persist($client);
            $em->flush();
            $AssortimentRepository->deleteMultipleAssortimentsByValeurAndEntreprise($clientOldAssortimentValeur, $clientCodeEntreprise);
        }

        $currentAssortiment = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(array("valeur" => $valeur));

        $paramsViews = array(
            'commercialInfo' => $this->commercialInfo(),
            "code_client" => $id,
            "valeur" => $valeur,
            "assortiment" => [ "nom" => $currentAssortiment->getNom(), "produits" => $this->fetchAssortimentTree($id, $valeur) ],
            "mode" => "edit"
        );

        // $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(array("client" => $id, "valeur" => $valeur)),

        return $this->render('SogedialIntegrationBundle:Admin/assortimentsclients:edit.html.twig', $paramsViews);
    }

    public function assortimentsClientDeleteAction($id, $valeur)
    {
        $em = $this->getDoctrine()->getManager();
        $assortimentClient = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(array("client" => $id, "valeur" => $valeur));
        $em->remove($assortimentClient);
        $em->flush();

        return $this->redirectToRoute(
            "sogedial_integration_admin_client_assortiments",
            [
                "id" => $id
            ]
        );
    }

    /**
     * @param Request $request
     * @param $status
     * @return Response
     */
    public function dashboardAction(Request $request, $status)
    {
        $response = new Response();
        $response->setMaxAge(300);

        if (!$response->isNotModified($request)) {
            $em = $this->getDoctrine()->getManager();
            $date = new \DateTime();
            $response->setLastModified($date);

            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $code_entreprise = false;
            if ($this->getUser()->getEntreprise() !== NULL) {
                $code_entreprise = $this->getUser()->getEntreprise()->getCode();
            }

            $commandesEncours = $em->getRepository('SogedialSiteBundle:Commande')->getOrdersToAdminDashboard($multiplyByPcb, $code_entreprise, $status);

            $paramsView = array(
                'commercialInfo' => $this->commercialInfo(),
                'commandeEncours' => $this->get('sogedial_integration.commande')->getCommandesAdmin($commandesEncours, $multiplyByPcb),
                'panierEncours' => $this->get('sogedial_integration.commande')->getPendingOrdersToAdminDashboard($multiplyByPcb, $code_entreprise, 5),
                'prochaineLivraison' => $em->getRepository('SogedialSiteBundle:Client')->getNextDeliveryDate($this->get('sogedial.multisite')->hasTemperature("ambient"), $this->get('sogedial.multisite')->hasTemperature("positiveCold"), $this->get('sogedial.multisite')->hasTemperature("negativeCold")),
                'preCommandeMode' => $this->getUser()->getPreCommande() != NULL,
            );

            $response->setContent($this->renderView('SogedialIntegrationBundle:Admin:index.html.twig', $paramsView));
        }

        return $response;

    }

    /**
     * @param Request $request
     * @param $status
     * @param $page
     * @return Response
     */
    public function productLoadAction(Request $request, $status, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;

        if ($this->getUser()->getEntreprise()) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $query = false;

        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $clients = $em->getRepository('SogedialSiteBundle:Client')
            ->getListClients2(strtoupper($status), $page, $code_entreprise, $query);

        $paramsView = [
            "mesClients" => $clients,
            "_societe_cc" => $code_entreprise
        ];

        return $this->render('SogedialIntegrationBundle:Admin:mesclients.list.html.twig', $paramsView);

    }

    /**
     * @param Request $request
     * @param $status
     * @param $page
     * @return Response
     */
    public function mesClientsAction(Request $request, $status, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $this->get('sogedial.multisite')->initSessionUserAdmin($this->get('security.token_storage')->getToken());

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Mes clients');

        $query = false;
        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $clients = $em->getRepository('SogedialSiteBundle:Client')->getListClients2($status, $page, $code_entreprise, $query);

        $viewParams = array(
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess($code_entreprise),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess($code_entreprise),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients($code_entreprise),
            'commercialInfo' => $this->commercialInfo(),
            'mesClients' => $clients,
            "_societe_cc" => $code_entreprise
        );

        return $this->render('SogedialIntegrationBundle:Admin:mesclients.html.twig', $viewParams);
    }

    /**
     * @param Request $request
     * @param string $status
     * @param int $page
     * @return Response
     */
    public function mesProspectsAction(Request $request, $status, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $multiSiteService = $this->get('sogedial.multisite');

        $code_entreprise = $multiSiteService->getSociete();

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Mes prospects');

        $query = false;
        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $isProspect = true;
        $prospects = $em->getRepository('SogedialSiteBundle:Client')->getListClients2($status, $page, $code_entreprise, $query, $isProspect);

        $viewParams = array(
            'numberOfProspectsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess($code_entreprise, true),
            'numberOfProspectsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients($code_entreprise, true),
            'commercialInfo' => $this->commercialInfo(),
            'mesProspects' => $prospects,
            "_societe_cc" => $code_entreprise,
        );
        return $this->render('SogedialIntegrationBundle:Admin:mesprospects.html.twig', $viewParams);
    }

    /**
     * @param Request $request
     * @param string $status
     * @param int $page
     * @return Response
     */
    public function prospectsLoadAction(Request $request, $status, $page)
    {

        $response = new Response();

        $em = $this->getDoctrine()->getManager();
        $multiSiteService = $this->get('sogedial.multisite');

        $code_entreprise = $multiSiteService->getSociete();
        $query = false;
        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $isProspect = true;
        $prospects = $em->getRepository('SogedialSiteBundle:Client')->getListClients2($status, $page, $code_entreprise, $query, $isProspect);

        $paramViews = array(
            'mesProspects' => $prospects,
            "_societe_cc" => $code_entreprise
        );
        $response->setContent($this->renderView('SogedialIntegrationBundle:Admin:mesprospects.list.html.twig', $paramViews));
        return $response;
    }


    /**
     * @param Request $request
     * @param string $codeProspect
     * @return Response JsonResponse
     */
    public function prospectsWithEnseigneAction(Request $request, $codeProspect)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;

        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        if ($code_entreprise == false) {
            $code_entreprise = $this->get('sogedial.multisite')->getSociete();
        }
        
        $enseigne = $em->getRepository('SogedialSiteBundle:Client')->getEnseigneByCodeProspect($codeProspect);

        $prospectsSameEnseigne = $em->getRepository('SogedialSiteBundle:Client')->getProspectsWithEnseigne($code_entreprise, $enseigne['code'], $codeProspect);
        return new JsonResponse(['data' => $prospectsSameEnseigne]);
    }

    public function assortimentsClientsWithEnseigneAction(Request $request, $codeClient)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        $entreprise = $this->getUser()->getEntreprise();

        if ($entreprise !== NULL) {
            $code_entreprise = $entreprise->getCode();
        }
        else {
            $code_entreprise = $this->get('sogedial.multisite')->getSociete();
        }

        $enseigne = $em->getRepository('SogedialSiteBundle:Client')->getEnseigneByCodeProspect($codeClient);

        $assortimentsSameEnseigne = $em->getRepository('SogedialSiteBundle:AssortimentClient')->getAssortimentsClientSameEnseigne($codeClient,$enseigne['code']);

        return new JsonResponse(['data' => $assortimentsSameEnseigne]);
    }

    /**
     * @param Request $request
     * @param int $stepId
     */
    public function addOrEditProspectAction(Request $request, $stepId, $mode)
    {
        $em = $this->getDoctrine()->getManager();
        $AssortimentRepository = $em->getRepository('SogedialSiteBundle:Assortiment');
        $ClientRepository = $em->getRepository('SogedialSiteBundle:Client');
        $EnseigneRepository = $em->getRepository('SogedialSiteBundle:Enseigne');
        $EntrepriseRepository = $em->getRepository('SogedialSiteBundle:Entreprise');
        $MetaClientRepository = $em->getRepository('SogedialSiteBundle:MetaClient');
        $UserRepository = $em->getRepository('SogedialUserBundle:User');
        $RegionRepository = $em->getRepository('SogedialSiteBundle:Region');
        $ZoneRepository = $em->getRepository('SogedialSiteBundle:Zone');

        $multiSiteService = $this->get('sogedial.multisite');
        $societe = $multiSiteService->getSociete();

        // If mode wasn't defined in the query string, retrieve it with its default value.
        $queriedMode = $request->query->get('mode');
        $mode = ($queriedMode ?: $mode);
        $prospectRoute = ($mode === 'edit' ? 'sogedial_integration_admin_update_prospect' : 'sogedial_integration_admin_ajout_prospect');
        $prospectListRoute = 'sogedial_integration_admin_mesprospects';

        // Try to retrieve prospect code.
        $codeProspect = $request->query->get('codeProspect');
        if ($codeProspect !== null) {
            // If a prospect code exists, fetch the client associated with it.
            $client = $ClientRepository->find($codeProspect);
        }
        if ($codeProspect === null || $client === null) {
            // In the other case, or if a client couldn't be found based on the given code, create a client and give it a randomly generated code.
            $client = new Client();
            //$societe = $multiSiteService->getSociete();
            $codeProspect = $ClientRepository->generateNewProspectCode($societe);
            $client->setCode($codeProspect);
            $codeRegionAdmin = $multiSiteService->getRegion();
            $regionAdmin = $RegionRepository->find($codeRegionAdmin);
            $client->setRegion($regionAdmin);
            $codeEntrepriseAdmin = $multiSiteService->getSociete();
            $entrepriseAdmin = $EntrepriseRepository->find($codeEntrepriseAdmin);
            $client->setEntreprise($entrepriseAdmin);
            $client->setIsProspect(true);
        }

        switch ($stepId) {
            case 1:
                $formType = new AddClientProspectStep1Type();
                $hasTarificationFeature = $multiSiteService->hasFeature('tarifs-tarification');
                $formType->setTarifsTarification($hasTarificationFeature);
                $formType->setRegion($multiSiteService->getRegion());
                $formType->setTarifsEnseigne(!$hasTarificationFeature);

                if ($mode === 'edit') {
                    // Store the previous value of the client's tarif or enseigne.
                    // If it is modified and persisted, we must force the admin to generate a new assortiment for the prospect.
                    if ($hasTarificationFeature) {
                        $clientOldTarificationOrEnseigne = $client->getTarification();
                    } else {
                        $clientOldTarificationOrEnseigne = $client->getEnseigne();
                    }
                }

                $form = $this->createForm($formType, $client);

                // Handle form submit.
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    // If tarification has been set, a 'sans enseigne' enseigne must be filled in.
                    if ($hasTarificationFeature && is_null($client->getEnseigne())) {
                        $codeSansEnseigne = $EnseigneRepository->getCodeSansEnseigneByRegion($client->getRegion()->getCode());
                        $sansEnseigne = $EnseigneRepository->find($codeSansEnseigne);
                        $client->setEnseigne($sansEnseigne);
                    }

                    if ($mode === 'edit') {
                        if ($hasTarificationFeature) {
                            $clientNewTarificationOrEnseigne = $client->getTarification();
                        } else {
                            $clientNewTarificationOrEnseigne = $client->getEnseigne();
                        }
                        // If the prospect's tarification or enseigne have been edited, force the generation of a new assortiment.
                        if ($clientOldTarificationOrEnseigne->getCode() !== $clientNewTarificationOrEnseigne->getCode()) {
                            $clientOldAssortiment = $client->getAssortiment();
                            $clientOldAssortimentValeur = $clientOldAssortiment->getValeur();
                            $clientCodeEntreprise = $client->getEntreprise()->getCode();
                            $client->setAssortiment(null);
                            // We must persist the modifications before deleting the old assortiment, otherwise the foreign key checks will throw an error.
                            $em->persist($client);
                            $em->flush();
                            $AssortimentRepository->deleteMultipleAssortimentsByValeurAndEntreprise($clientOldAssortimentValeur, $clientCodeEntreprise);
                        }
                    }

                    $client->setTypologieClient($form->getData()->getTypologieClient());
                    if (($form->getData()->getCommentaireProspect() !== null)) {
                        $client->setCommentaireProspect(trim(($form->getData()->getCommentaireProspect())));
                    }
                    $em->persist($client);
                    $em->flush();

                    // Go to next step
                    return $this->redirectToRoute(
                        $prospectRoute,
                        [
                            'codeProspect' => $codeProspect,
                            'mode' => $mode,
                            'stepId' => $stepId + 1,
                        ]
                    );
                }

                $stepTitle = 'informations client';
                break;
            case 2:
                $meta = $client->getMeta();

                if ($meta !== null) {
                    $codeMeta = $meta->getCode();
                    $user = $UserRepository->findOneByMeta($codeMeta);
                } else {
                    // Create a meta for the client.
                    $meta = new MetaClient();
                    $codeMeta = $MetaClientRepository->generateNewMetaCode();
                    $meta->setCode($codeMeta);
                    $meta->setLibelle($client->getNom());
                    $em->persist($meta);

                    // Update client info.
                    $client->setMeta($meta);
                    $client->setEActif(true);
                    $em->persist($client);

                    // Create a new fos_user related to the new meta.
                    $user = new User();
                }

                $formType = new AddClientProspectStep2Type();
                $form = $this->createForm($formType, $user);

                // Handle form submit.
                $form->handleRequest($request);
                $formData = $form->getData();
                if ($form->isSubmitted() && $form->isValid()) {
                    if (trim($formData->getUsername()) !== "" && trim($formData->getPassword()) !== "") {
                        $user->setUsername($formData->getUsername());
                        $user->setPlainPassword($formData->getPassword());
                        $emailClient = $client->getEmail();
                        $user->setEmail($emailClient);
                        $user->setEnabled(true);
                        $societe = $multiSiteService->getSociete();
                        $entrepriseObject = $EntrepriseRepository->find($societe);
                        $user->setEntreprise($entrepriseObject);
                        $user->setMeta($meta);
                        $user->setEntrepriseCourante($societe);
                        $user->addRole('ROLE_USER');
                        $user->setEtat('client');
                        $em->persist($user);

                        $em->flush();

                        // Go to next step
                        return $this->redirectToRoute(
                            $prospectRoute,
                            [
                                'codeProspect' => $codeProspect,
                                'mode' => $mode,
                                'stepId' => $stepId + 1,
                            ]
                        );
                    } else {
                        $metaError = new FormError("Vous devez saisir un identifiant et un mot de passe de compte pour votre client.");
                        $form->addError($metaError);
                    }
                }

                $stepTitle = 'Informations utilisateur';
                break;
            case 3:
                $prospectObj = $this->getDoctrine()->getManager()->getRepository('SogedialSiteBundle:Client')->findOneByCode($codeProspect);
                $userObject = $this->getDoctrine()->getManager()->getRepository('SogedialUserBundle:User')->findOneBy(array('meta' => $prospectObj->getMeta()->getCode(), 'entrepriseCourante' => $prospectObj->getEntreprise()->getCode()));
                if ($userObject instanceof User) {

                    $formType = new AddClientProspectStep3Type();
                    $form = $this->createForm($formType, $userObject);

                    // Handle form submit.
                    $form->handleRequest($request);
                    $formData = $form->getData();

                    if ($form->isSubmitted() && $form->isValid()) {
                        $userObject->setDateDebutValidite($formData->getDateDebutValidite());
                        $userObject->setDateFinValidite($formData->getDateFinValidite());
                        $em->persist($userObject);
                        $em->flush();

                            // Go to next step
                        return $this->redirectToRoute(
                            $prospectRoute,
                            [
                                'codeProspect' => $codeProspect,
                                'mode' => $mode,
                                'stepId' => $stepId + 1,
                            ]
                        );
                    }
                }
                $stepTitle = 'Dates de validité';
                break;
            case 4:
                $userObj = null;

                if ($client->getMeta() !== null) {
                    $userObj = $UserRepository->findOneByMeta($client->getMeta()->getCode());
                }
                $codeEntrepriseAdmin = $multiSiteService->getSociete();
                $entrepriseAdmin = $EntrepriseRepository->find($codeEntrepriseAdmin);
                $formType = new SetupProspectOptionsType();
                $formType->setCodeEntreprise($entrepriseAdmin->getCode());
                $formType->setAmbient($multiSiteService->hasTemperature('ambient'));
                $formType->setPositiveCold($multiSiteService->hasTemperature('positiveCold'));
                $formType->setNegativeCold($multiSiteService->hasTemperature('negativeCold'));
                $form = $this->createForm($formType, $userObj);

                if ($userObj instanceof User) {
                    $form->setData($userObj);
                }

                $form->handleRequest($request);
                $formData = $form->getData();
                if ($form->isSubmitted() && ($client instanceof Client) && $form->isValid() && $request->request->get('prospect_options') !== null) {

                    if (array_key_exists('zoneSec', $formData) && $formData['zoneSec'] instanceof Zone) {
                        $zoneSec = $ZoneRepository->findOneByCode($formData['zoneSec']->getCode());
                        $userObj->setZoneSec($zoneSec);
                    }

                    if (array_key_exists('zoneFrais', $formData) && $formData['zoneFrais'] instanceof Zone) {
                        $zoneFrais = $ZoneRepository->findOneByCode($formData['zoneFrais']->getCode());
                        $userObj->setZoneFrais($zoneFrais);
                    }
                    if (array_key_exists('zoneSurgele', $formData) && $formData['zoneSurgele'] instanceof Zone) {
                        $zoneSurgele = $ZoneRepository->findOneByCode($formData['zoneSurgele']->getCode());
                        $userObj->setZoneSurgele($zoneSurgele);
                    }

                    if ($formData->getMontantFranco() == null) {
                        $userObj->setMontantFranco($multiSiteService->getValue("franco"));
                    } else {
                        $userObj->setMontantFranco($formData->getMontantFranco());
                    }

                    $em->persist($userObj);
                    $em->flush();

                    // TODO : @Sictoz à remettre après la correction du problème de chargement des assortiments
                    if($codeEntrepriseAdmin === '401') {
                        return $this->redirectToRoute($prospectListRoute);
                    } else {
                        // Go to next step
                        return $this->redirectToRoute(
                            $prospectRoute,
                            [
                                'codeProspect' => $codeProspect,
                                'mode' => $mode,
                                'stepId' => $stepId + 1,
                            ]
                        );
                    }
                }

                $stepTitle = "Sélection des options";
                break;
            case 5:
                $stepTitle = "Sélection de l'assortiment";
                break;
            default:
                break;
        }

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addRouteItem('Mes prospects', 'sogedial_integration_admin_mesprospects');
        $breadcrumbs->addItem(
            ($mode === 'edit' ? 'Edition du prospect ' . $codeProspect : 'Création prospect')
        );

        $paramViews = array(
            'form' => (isset($form) ? $form->createView() : null),
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber(),
            'commercialInfo' => $this->commercialInfo(),
            'franco' => $multiSiteService->hasFeature('franco'),
            'zoneSec' => $multiSiteService->hasTemperature('ambient'),
            'zoneFrais' => $multiSiteService->hasTemperature('positiveCold'),
            'zoneSurgele' => $multiSiteService->hasTemperature('negativeCold'),
            'tarification' => $this->get('sogedial.multisite')->hasFeature('tarifs-tarification'),
            'stepId' => $stepId,
            'codeProspect' => $codeProspect,
            'mode' => $mode,
            'stepTitle' => $stepTitle,
            'prospectRoute' => $prospectRoute,
            'codeSociete' => $societe
        );
        return $this->render('SogedialIntegrationBundle:Admin:ajouter-prospect-step' . $stepId . '.html.twig', $paramViews);
    }

    public function submitClientAssortimentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $clientRepository = $em->getRepository('SogedialSiteBundle:Client');
        $assortimentRepository = $em->getRepository('SogedialSiteBundle:Assortiment');
        $assortimentClientRepository = $em->getRepository('SogedialSiteBundle:AssortimentClient');
        $assortimentService = $this->get('sogedial_site.assortiment');

        $requestNodes = $this->get('request')->request->all();
        $nodesDecoded = json_decode(stripslashes($requestNodes['data']),true);

        $nodes = $nodesDecoded['nodes'];
        $mode = $nodesDecoded['mode'];
        $nom = $nodesDecoded['assortiment_nom'];
        $codeClient = $nodesDecoded['codeClient'];
        $response = new JsonResponse();

        try {
            if ($mode === 'edit') {
                $valeur = $nodesDecoded['valeurAssortiment'];
                $client = $clientRepository->find($codeClient);
                $assortimentClient = $assortimentClientRepository->findOneBy(array("client" => $client, "valeur" => $valeur));
                $assortimentClient->setAssortiment(NULL);
                $client->setAssortiment(NULL);
                $em->persist($client);
                $em->persist($assortimentClient);
                $em->flush();

                $client = $clientRepository->find($codeClient);
                $clientOldAssortiment = $client->getAssortiment();
                $assortimentRepository->deleteMultipleAssortimentsByValeurAndEntreprise($valeur, $client->getEntreprise()->getCode());

                $assortimentCode = $assortimentService->generateAssortiment($nodes, $codeClient, false, $valeur);
                $assortiment = $assortimentRepository->findOneByCode($assortimentCode);
                $client->setAssortiment($assortiment);
                $assortimentClient->setAssortiment($assortiment);
                if (strlen($nom) > 0) {
                    $assortimentClient->setNom($nom);
                }

                $em->persist($client);
                $em->flush();

                $response->setData(array(
                    'valeur' => $valeur,
                    "assocode" => $assortimentCode,
                ));

            } else {
                $client = $clientRepository->find($codeClient);
                $clientOldAssortiment = $client->getAssortiment();
                if (!is_null($clientOldAssortiment)) {
                    $clientOldAssortimentValeur = $clientOldAssortiment->getValeur();
                    $clientCodeEntreprise = $client->getEntreprise()->getCode();
                    $client->setAssortiment(null);
                    // We must persist the modifications before deleting the old assortiment, otherwise the foreign key checks will throw an error.
                    $em->persist($client);
                    $em->flush();
                    //$assortimentRepository->deleteMultipleAssortimentsByValeurAndEntreprise($clientOldAssortimentValeur, $clientCodeEntreprise);
                }

                $assortimentCode = $assortimentService->generateAssortiment($nodes, $codeClient);
                // Fill the client's assortiment code, which is any of its assortiment's code.
                $assortiment = $assortimentRepository->findOneByCode($assortimentCode);
                $client->setAssortiment($assortiment);

                $assortimentClient = $assortimentClientRepository->findOneBy(array("client" => $client, "valeur" => $assortimentCode));
                //security: only one client for a "valeur"
                if ($assortimentClient === NULL) {
                    $assortimentClient = new AssortimentClient();
                    $assortimentClient->setNom($nodesDecoded['valeurAssortiment']);
                    $assortimentClient->setValeur($assortiment->getValeur());
                    $assortimentClient->setClient($client);
                    $assortimentClient->setAs400assortiment(false);
                    $assortimentClient->setAssortiment($assortiment);
                    $em->persist($assortimentClient);
                }

                $em->persist($client);
                $em->flush();
                $response->setData(array(
                    'data' => $assortimentCode
                ));
            }
        } catch (\Exception $e) {
            $response->setStatusCode(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, json_encode($e->getMessage()));
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function fetchProspectAssortimentTreeAction(Request $request, $codeProspect, $valeurAssortiment = null)
    {
        return $this->fetchAssortimentTree($codeProspect, $valeurAssortiment);
    }

    private function fetchAssortimentTree($codeClient, $valeurAssortiment)
    {
        $assortimentService = $this->get('sogedial_site.assortiment');

        // Generate the jstree data, ready to be injected.
        $jstreeAssortiment = $assortimentService->generateJstreeAssortiment($codeClient, $valeurAssortiment);

        return new JsonResponse($jstreeAssortiment);
    }

    public function fetchClientAssortimentTreeAction(Request $request, $codeClient, $valeurAssortiment = null)
    {
        return $this->fetchAssortimentTree($codeClient, $valeurAssortiment);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function fetchNodeChildrenAction(Request $request)
    {
        $loadedNodeId = $request->query->get('nodeId');
        $loadedNodeType = $request->query->get('nodeType');
        $assortimentService = $this->get('sogedial_site.assortiment');

        // Fetch all child categories/items in the database, with the unique Id and the name of each one.
        list($children, $childrenType) = $assortimentService->getChildrenFromParentIdAndType($loadedNodeId, $loadedNodeType);

        // Organize children data in node format, ready to be injected in the jstree.
        $childNodes = $assortimentService->mapDatabaseObjectsToNodeFormat($children, $childrenType);

        return new JsonResponse($childNodes);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function submitProspectAssortimentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ClientRepository = $em->getRepository('SogedialSiteBundle:Client');
        $AssortimentRepository = $em->getRepository('SogedialSiteBundle:Assortiment');
        $assortimentClientRepository = $em->getRepository('SogedialSiteBundle:AssortimentClient');
        $AssortimentService = $this->get('sogedial_site.assortiment');

        $requestNodes = $this->get('request')->request->all();
        $nodes = json_decode(stripslashes($requestNodes['data']),true);

        $codeProspect = $nodes['codeProspect'];

        $response = new JsonResponse();
        try {
            $client = $ClientRepository->find($codeProspect);
            $clientOldAssortiment = $client->getAssortiment();
            if (!is_null($clientOldAssortiment)) {
                $clientOldAssortimentValeur = $clientOldAssortiment->getValeur();
                $clientCodeEntreprise = $client->getEntreprise()->getCode();
                $client->setAssortiment(null);
                // We must persist the modifications before deleting the old assortiment, otherwise the foreign key checks will throw an error.
                $em->persist($client);
                $em->flush();
                $assortimentClientRepository->deleteAssortimentByValeur($clientOldAssortimentValeur);
                $AssortimentRepository->deleteMultipleAssortimentsByValeurAndEntreprise($clientOldAssortimentValeur, $clientCodeEntreprise);
            }
            $assortimentClientCourant = $assortimentClientRepository->findOneBy(array("client" => $client->getCode(), "assortimentCourant" => true));
            $assortimentCode = $AssortimentService->generateAssortiment($nodes['nodes'], $codeProspect, true);
            // Fill the client's assortiment code, which is any of its assortiment's code.
            $assortiment = $AssortimentRepository->findOneByCode($assortimentCode);
            $client->setAssortiment($assortiment);
            $assortimentClient = $assortimentClientRepository->findOneBy(array("client" => $client->getCode(), "valeur" => $assortimentCode));
            //security: only one client for a "valeur"
            if ($assortimentClient === NULL) {
                $assortimentClient = new AssortimentClient();
                $assortimentClient->setValeur($assortiment->getValeur());
                $assortimentClient->setClient($client);
                $assortimentClient->setAs400assortiment(false);
                $assortimentClient->setAssortiment($assortiment);
                if (!$assortimentClientCourant) {
                    $assortimentClient->setAssortimentCourant(true);
                }
                $assortimentClient->setNom($assortimentClient->getValeur());
                $em->persist($assortimentClient);
            }

            $em->persist($client);
            $em->flush();
            $response->setData(array(
                'data' => $assortimentCode
            ));
        } catch (\Exception $e) {
            $response->setStatusCode(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, json_encode($e->getMessage()));
        }

        return $response;
    }

    public function ficheClientAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        //si le parametre n'est pas numerique=> c'est le code client : on cherche alors l'id
        $hasLogin = TRUE;
        $clientUser = null;

        if (!is_numeric($id)) {
            $clientUser = $em->getRepository('SogedialSiteBundle:Client')->getUserFromClientId($id);
            // test si $clientUser est vide ou pas ==> client a un compte ou pas
            if ($clientUser != null) {
                $id = $clientUser->getId();
            } else {
                $hasLogin = FALSE;
            }
        }

        if ($hasLogin) {
            $entreprise = $this->get('sogedial.multisite')->getSociete();
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($id);

            $societeObject = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($entreprise);
            $societeLabelle = $societeObject->getNomEnvironnement();
            $commandeEncours = $em->getRepository('SogedialSiteBundle:Commande')->getOrdersByUser($id, $clientInfo["code"], $entreprise, null);
            $result = $this->get('sogedial_integration.catalogue')->getCommande2($id, $entreprise);
        } else {
            // ICI le client existe dans Client mais pas dans User (n'a pas d'accès au site)
            // On va donc chercher ces informations dans Client
            $clientInfo = $em->getRepository('SogedialSiteBundle:Client')->getClientInformation($id);
            $commandeEncours = null;
            $result = array('order' => null, 'totalAmount' => null);
        }

        $paramViews = array(
            'societe' => $societeLabelle,
            'commandeEncours' => $commandeEncours,
            'commercialInfo' => $this->commercialInfo(),
            'clientInfo' => $clientInfo,
            'result' => $result,
            'hasLogin' => $hasLogin,
            'routeName' => 'sogedial_integration_admin_client'
        );

        return $this->render('SogedialIntegrationBundle:Admin:client.html.twig', $paramViews);
    }

    public function trackingClientsAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        $clients = array();

        $trackedUsers = $this
            ->getDoctrine()
            ->getRepository('SogedialUserBundle:User')
            ->getTrackedUsers($code_entreprise, $page);

        foreach ($trackedUsers as $key => $element) {
            $tmp = array();
            $tmp['userId'] = $element->getId();
            $tmp['updated']['date'] = $element->getUpdated()->format('Y-m-d');
            $tmp['paysVente'] = $element->getPaysVente();
            $tmp['chiffreAffaire'] = $element->getChiffreAffaire();
            $tmp['prenom'] = $element->getPrenom();
            $tmp['nom'] = $element->getNom();
            $tmp['login'] = $element->getUsername();
            $tmp['age'] = round((time() - strtotime($element->getCreated()->format('Y-m-d H:i:s'))) / (60 * 60 * 24));

            $tmp['temps'] = $this->get('sogedial.time.converter')->sec_to_time(round($element->getTotalTime()));
            $tmp['connexion'] = $element->getCountConnexion();
            $tmp['derniereConnexion'] = $element->getLastLogin() === null ? " " : $element->getLastLogin()->format('d/m/Y H:i:s  ');
            $tmp['nomClient'] = $element->getMeta()->getLibelle();

            $trackedUsers[$key] = $tmp;
        }

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Tracking clients');

        $paramViews = array(
            'trackedUsers' => $trackedUsers,
            'commercialInfo' => $this->commercialInfo()
        );
        return $this->render('SogedialIntegrationBundle:Admin:tracking.html.twig', $paramViews);
    }
    public function trackingClientsLoadAction($page = 1)
    {
        $response = new Response();

        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $trackedUsers = $this
            ->getDoctrine()
            ->getRepository('SogedialUserBundle:User')
            ->getTrackedUsers($code_entreprise, $page);

        foreach ($trackedUsers as $key => $element) {
            $tmp = array();
            $tmp['userId'] = $element->getId();
            $tmp['updated']['date'] = $element->getUpdated()->format('Y-m-d');
            $tmp['paysVente'] = $element->getPaysVente();
            $tmp['chiffreAffaire'] = $element->getChiffreAffaire();
            $tmp['prenom'] = $element->getPrenom();
            $tmp['nom'] = $element->getNom();
            $tmp['login'] = $element->getUsername();
            $tmp['age'] = round((time() - strtotime($element->getCreated()->format('Y-m-d H:i:s'))) / (60 * 60 * 24));

            $tmp['temps'] = $this->get('sogedial.time.converter')->sec_to_time(round($element->getTotalTime()));
            $tmp['connexion'] = $element->getCountConnexion();
            $tmp['derniereConnexion'] = $element->getLastLogin() === null ? " " : $element->getLastLogin()->format('d/m/Y H:i:s  ');
            $tmp['nomClient'] = $element->getMeta()->getLibelle();

            $trackedUsers[$key] = $tmp;
        }

        $paramViews = array(
            'trackedUsers' => $trackedUsers
        );
        $response->setContent($this->renderView('SogedialIntegrationBundle:Admin:trackingClients.list.html.twig', $paramViews));
        return $response;
    }

    public function commandeLoadAction(Request $request, $status, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        $response->setMaxAge(300);

        if (!$response->isNotModified($request)) {
            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $code_entreprise = false;
            if ($this->getUser()->getEntreprise() !== NULL) {
                $code_entreprise = $this->getUser()->getEntreprise()->getCode();
            }

            $commandesEncours = $em->getRepository('SogedialSiteBundle:Commande')->getOrdersToAdmin($multiplyByPcb, $code_entreprise, $status, $page);

            $paramViews = array(
                'commandeCounterByStatus' => $this->get('sogedial_integration.commande')->getCommandeCounterAdmin($code_entreprise),
                'commandesEncours' => $this->get('sogedial_integration.commande')->getCommandesAdmin($commandesEncours, $multiplyByPcb),
                'commercialInfo' => $this->commercialInfo(),
                'preCommandeMode' => $this->getUser()->getPreCommande() != NULL,
                'societe' => $code_entreprise,
                'admin' => true
            );

            $response->setContent($this->renderView('SogedialIntegrationBundle:Admin:mes-commandes-clients-list.html.twig', $paramViews));
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param $status
     * @param $page
     * @return Response
     */
    public function commandesClientsAction(Request $request, $status, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        $response->setMaxAge(300);

        if (!$response->isNotModified($request)) {
            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $code_entreprise = false;
            if ($this->getUser()->getEntreprise() !== NULL) {
                $code_entreprise = $this->getUser()->getEntreprise()->getCode();
            }

            // Create breadcrumb nodes
            $breadcrumbs = $this->get("white_october_breadcrumbs");
            $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
            $breadcrumbs->addItem('Commandes clients');

            $commandesEncours = $em->getRepository('SogedialSiteBundle:Commande')->getOrdersToAdmin($multiplyByPcb, $code_entreprise, $status, $page);

            $paramViews = array(
                'commandeCounterByStatus' => $this->get('sogedial_integration.commande')->getCommandeCounterAdmin($code_entreprise),
                'commandesEncours' => $this->get('sogedial_integration.commande')->getCommandesAdmin($commandesEncours, $multiplyByPcb),
                'commercialInfo' => $this->commercialInfo(),
                'preCommandeMode' => $this->getUser()->getPreCommande() != NULL,
                'societe' => $code_entreprise
            );

            $response->setContent($this->renderView('SogedialIntegrationBundle:Admin:commandes.clients.html.twig', $paramViews));
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Commande $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function detailCommandeAction(Request $request, Commande $order)
    {
        $response = new Response();
        $response->setMaxAge(300);

        if (!$response->isNotModified($request)) {
            $listProductsByRayon = array();
            $listRayon = array();
            $currentCustomer = $order->getUser();
            $em = $this->getDoctrine()->getManager();
            $currentOrder = null;
            $clientInfo = null;

            if ($currentCustomer) {
                //commande type 1 & 2
                $currentOrder = $em->getRepository('SogedialSiteBundle:Commande')->getOrderInfo2($currentCustomer->getId(), $order);
                $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($currentCustomer->getId());
            } else {
                //commande type 3
                $currentOrder = $em->getRepository('SogedialSiteBundle:Commande')->getOrderInfoByClient($order->getClient(), $order);
                $clientInfo = $em->getRepository('SogedialSiteBundle:Client')->getClientInformation($order->getClient()->getCode());
            }

            $bonPreparations = $em->getRepository('SogedialSiteBundle:BonPreparation')->findBy(array("commande" => $order->getId()));
            $bonPreparationsTotal = [];
            $bonPreparationsTotal["totalColisFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumColisFacturation($order->getId());
            $bonPreparationsTotal["totalMontantFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumMontantFacturation($order->getId());

            if (null == $order) {
                return $this->redirect($this->generateUrl('SogedialSite_catalogue_integration'));
            }

            $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
            $stockColis = $multiplyByPcb;

            $orderProducts = $em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($order->getId(), $order->getTemperatureCommande(), $multiplyByPcb, $stockColis, false);
            $totalItem = (int)count($orderProducts['result']);
            foreach ($orderProducts['tree'] as $productByRayon) {
                $listRayon[] = $productByRayon['fr'];
                for ($i = 0; $i < $totalItem; $i++) {
                    if ($orderProducts['result'][$i]['sf'] == $productByRayon['id']) {
                        $listProductsByRayon[] = $orderProducts['result'][$i];
                    }
                }
            }

            if ($order->getClient() === NULL && $order->getUser() !== NULL) {
                $user = $em->getRepository('SogedialUserBundle:User')->findOneBy(array("id" => $order->getUser()));
                $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $user->getMeta()->getCode(), "entreprise" => $order->getEntreprise()));
                $order->setClient($client);
                $em->persist($order);
                $em->flush();
            }

            if ($order->getValidator() !== NULL && $order->getValidator()->getEtat() == 'client') {
                $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $order->getValidator()->getMeta()->getCode(), "entreprise" => $order->getEntreprise()));
                $displayName = $client->getNom();
            } elseif ($order->getValidator() !== NULL && $order->getValidator()->getEtat() !== 'client') {
                $displayName = $order->getValidator()->getNom() . ' ' . $order->getValidator()->getPrenom();
            }

            $clientCodeEntreprise = substr($clientInfo['code'], 0, 3);
            $clientCodeEntrepriseObject = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($clientCodeEntreprise);
            $societe = $clientCodeEntrepriseObject->getNomEnvironnement();

            // Retrieve order status
            $orderStatusLibelle = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneByOrder($order)->getOrderStatus()->getLibelle();

            $poidsTotal = null;
            $volumeTotal = null;
            if ($societe === 'sogedial') {
                $totalVolumeWeight = $em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order->getId());
                $poidsTotal = $totalVolumeWeight["poidsTotal"] ? $totalVolumeWeight["poidsTotal"] : $order->getPoidsCommande();
                $volumeTotal = $totalVolumeWeight["volumeTotal"] ? $totalVolumeWeight["volumeTotal"] : $order->getVolumeCommande();
            }

            $paramViews = array(
                "admin" => true,
                "bonPreparations" => $bonPreparations,
                "bonPreparationsTotal" => $bonPreparationsTotal,
                'order' => $order,
                'listProductsByRayon' => $listProductsByRayon,
                'commercialInfo' => $this->commercialInfo(),
                'nbrProduct' => $totalItem,
                'listRayons' => $listRayon,
                'clientInfo' => $clientInfo,
                'orderTotalAmount' => $order->getMontantCommande(),
                'orderNumber' => $order->getNumero(),
                'orderId' => $order->getId(),
                'orderUpdate' => $order->getUpdatedAt(),
                'orderDeliveryDate' => $order->getDeliveryDate(),
                'orderStatut' => $currentOrder['key'],
                'state' => ($currentOrder === null) ? 0 : 1,
                'baseUrl' => $this->container->getParameter('baseUrl'),
                'commentaire' => $order->getCommentaire(),
                'preCommandeMode' => false, // @TODO
                'displayName' => $displayName,
                'societe' => $societe,
                'region' => $this->get('sogedial.multisite')->getRegion(),
                'orderStatusLibelle' => $orderStatusLibelle,
                'volumeTotal' => $volumeTotal,
                'poidsTotal' => $poidsTotal
            );

            $response->setContent($this->renderView('SogedialIntegrationBundle:Commande:detail-historique-commande.html.twig', $paramViews));
        }

        return $response;
    }

    /**
     * clone de detailCommandeAction pour afficher rapidement un panier en mode lecture
     * a modifier pour faire plus proprement
     * @param Request $request
     * @param Commande $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function detailPanierAction(Request $request, Commande $order)
    {
        $em = $this->getDoctrine()->getManager();
        $currentCustomer = $order->getUser();
        $listProductsByRayon = array();
        $ps = $this->get('sogedial.product');
        $prm = $this->get('sogedial.promotion');
        $listRayon = array();
        $currentOrder = null;
        $clientInfo = null;

        if ($currentCustomer) {
            //commande type 1 & 2
            $currentOrder = $em->getRepository('SogedialSiteBundle:Commande')->getOrderInfo2($currentCustomer->getId(), $order);
            $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($currentCustomer->getId());
        } else {
            //commande type 3
            $currentOrder = $em->getRepository('SogedialSiteBundle:Commande')->getOrderInfoByClient($order->getClient(), $order);
            $clientInfo = $em->getRepository('SogedialSiteBundle:Client')->getClientInformation($order->getClient()->getCode());
        }

        $bonPreparations = $em->getRepository('SogedialSiteBundle:BonPreparation')->findBy(array("commande" => $order->getId()));
        $bonPreparationsTotal = [];
        $bonPreparationsTotal["totalColisFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumColisFacturation($order->getId());
        $bonPreparationsTotal["totalMontantFacturation"] = $em->getRepository('SogedialSiteBundle:BonPreparation')->getSumMontantFacturation($order->getId());

        if (null == $order) {
            return $this->redirect($this->generateUrl('SogedialSite_catalogue_integration'));
        }

        $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;

        $orderProducts = $em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrder($order->getId(), $multiplyByPcb, $stockColis);

        $productsByRayon = $this->get('sogedial_integration.catalogue')->getOrderProductTree($orderProducts);

        $unitedPromos = $prm->getUnitedPromos($clientInfo['enseigneClientCode'], $clientInfo['code']);

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
        $totalItem = (int)count($orderProducts);
        for ($i = 0; $i < $totalItem; $i++) {
            $product = $em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $orderProducts[$i]["code"]));

            $priceAndStock = $ps->getActualProductPriceAndStock($product,
                // $productsByClientInPromotion, $productsByEnseInPromotion, $promos,
                $unitedPromos, $clientInfo['enseigneClientCode'], $clientInfo['tarificationClientCode']);

            $orderProducts[$i]['isPromo'] = $priceAndStock['isPromo'];
            $orderProducts[$i]['prixHt'] = $priceAndStock['priceArray'];
            $orderProducts[$i]['stock'] = $priceAndStock['stock'];

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
            for ($i = 0; $i < $totalItem; $i++) {
                if ($orderProducts[$i]['sf'] == $productByRayon['id']) {
                    $listProductsByRayon[] = $orderProducts[$i];
                }
            }
        }

        if ($order->getValidator() !== NULL && $order->getValidator()->getEtat() == 'client') {
            $displayName = $order->getClient()->getNom();
        } elseif ($order->getValidator() !== NULL && $order->getValidator()->getEtat() !== 'client') {
            $displayName = $order->getValidator()->getNom() . ' ' . $order->getValidator()->getPrenom();
        } else {
            $displayName = "";
        }

        $clientCodeEntreprise = substr($clientInfo['code'], 0, 3);
        $clientCodeEntrepriseObject = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($clientCodeEntreprise);
        $societe = $clientCodeEntrepriseObject->getNomEnvironnement();

        // Retrieve order status
        $orderStatusLibelle = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneByOrder($order)->getOrderStatus()->getLibelle();

        $poidsTotal = null;
        $volumeTotal = null;
        if ($societe === 'sogedial') {
            $totalVolumeWeight = $em->getRepository('SogedialSiteBundle:Produit')->getOrderTotalVolumeWeight($order->getId());
            $poidsTotal = $totalVolumeWeight["poidsTotal"] ? $totalVolumeWeight["poidsTotal"] : $order->getPoidsCommande();
            $volumeTotal = $totalVolumeWeight["volumeTotal"] ? $totalVolumeWeight["volumeTotal"] : $order->getVolumeCommande();
        }

        $paramViews = array(
            "admin" => true,
            "bonPreparations" => $bonPreparations,
            "bonPreparationsTotal" => $bonPreparationsTotal,
            'order' => $order,
            'listProductsByRayon' => $listProductsByRayon,
            'commercialInfo' => $this->commercialInfo(),
            'nbrProduct' => $totalItem,
            'listRayons' => $listRayon,
            'clientInfo' => $clientInfo,
            'orderProducts' => $products,
            'orderNumber' => $order->getNumero(),
            'orderId' => $order->getId(),
            'orderUpdate' => $order->getUpdatedAt(),
            'orderDeliveryDate' => $order->getDeliveryDate(),
            'orderStatut' => $currentOrder['key'],
            'displayName' => $displayName,
            'state' => ($currentOrder === null) ? 0 : 1,
            'baseUrl' => $this->container->getParameter('baseUrl'),
            'commentaire' => $order->getCommentaire(),
            'preCommandeMode' => false,
            'societe' => $societe,
            'orderStatusLibelle' => $orderStatusLibelle,
            'volumeTotal' => $volumeTotal,
            'poidsTotal' => $poidsTotal
        );
        //return $this->render('SogedialIntegrationBundle:Admin:commande.client.content.html.twig', $paramViews);
        return $this->render('SogedialIntegrationBundle:Admin:detail-panier.html.twig', $paramViews);
    }

    public function commandeMoqAction(Request $request)
    {
        if ($this->getUser()->getPrecommande() === NULL) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $em = $this->getDoctrine()->getManager();
        $references = array();
        foreach ($request->get('references') as $lignecommande => $quantity) {
            if (!(is_numeric($quantity) && is_int(intval($quantity)))) {
                return $this->render(
                    'SogedialSiteBundle:Site:modalConfirm.html.twig',
                    array(
                        'message' => "La valeur de quantité n'est pas un entier valide"
                    )
                );
            }

            $references[$lignecommande] = $quantity;

            $qb = $em->createQueryBuilder();
            $q = $qb->update('SogedialSiteBundle:LigneCommande', 'lc')
                ->set('lc.quantite', '?2')
                ->set('lc.montantTotal', 'lc.quantite * lc.prixUnitaire * lc.pcb')
                ->where('lc.id = ?1')
                ->setParameter(1, $lignecommande)
                ->setParameter(2, $quantity)
                ->getQuery();
            $p = $q->execute();
        }

        return new JsonResponse(
            $references
        );
    }

    public function validMoqAction(Request $request)
    {
        if ($this->getUser()->getPrecommande() === NULL) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $em = $this->getDoctrine()->getManager();
        $lignecommandeRepository = $em->getRepository(
            'SogedialSiteBundle:LigneCommande'
        );
        $historiqueLigneCommandeRepository = $em->getRepository(
            'SogedialSiteBundle:HistoriqueLigneCommande'
        );
        $commandeRepository = $em->getRepository(
            'SogedialSiteBundle:Commande'
        );
        $historiqueLigneCommandeService = $this->get("sogedial.historique_ligne_commande");
        $commandeService = $this->get('sogedial_integration.commande');
        $references = $request->get('references');
        $commandes = array();

        foreach ($references as $lignecommandeId => $quantity) {
            if (!(is_numeric($quantity) && is_int(intval($quantity)))) {
                return $this->render(
                    'SogedialSiteBundle:Site:modalConfirm.html.twig',
                    array(
                        'message' => "La valeur de quantité n'est pas un entier valide"
                    )
                );
            }

            $lignecommande = $lignecommandeRepository->findOneById($lignecommandeId);
            $lignecommande->setMoq(true);
            $em->persist($lignecommande);

            $historiqueLigneCommande = $historiqueLigneCommandeRepository->findOneBy(array("ligneCommande" => $lignecommandeId));
            if ($historiqueLigneCommande->getQuantite() !== $lignecommande->getQuantite()) {
                $historiqueLigneCommande = $historiqueLigneCommandeService->create(
                    $lignecommande,
                    $this->getUser(),
                    $lignecommande->getQuantite()
                );
                $em->persist($historiqueLigneCommande);
            }

            $commandesChilds = $commandeRepository->findByParent($lignecommande->getCommande());
            foreach ($commandesChilds as $commande) {
                $commandes[$commande->getId()] = $commande;
            }

        }
        $em->flush();

        foreach ($commandes as $commande) {
            $commandeService->setMontantCommandFromCommandLines($commande->getId(), $commande->getParent());
            $commandes[$commande->getId()] = $commande->getParent();
        }

        return new JsonResponse(
            array('nbCommandes' => count($commandes))
        );
    }

    public function commandesMOQAction($status)
    {
        if ($this->getUser()->getPrecommande() === NULL) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $em = $this->getDoctrine()->getManager();
        $societe = $this->getUser()->getEntreprise()->getCode();

        $userRepository = $em->getRepository('SogedialUserBundle:User');
        $commandeService = $this->get('sogedial_integration.commande');
        $users = $userRepository->getNoCommercialFosUsers(true);

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Validation quantités');

        $paramViews = array(
            'users' => $users,
            'commercialInfo' => $this->commercialInfo(),
            'commandes' => $commandeService->getCommandesMOQ($status, $societe),
            'baseUrl' => $this->container->getParameter('baseUrl')
        );

        return $this->render(
            'SogedialIntegrationBundle:Admin:commande.moq.html.twig',
            $paramViews
        );
    }

    public function commandesMOQProductQMinAction($ean13Produit, $codeClient)
    {
        $em = $this->getDoctrine()->getManager();
        $qMinArray = $em->getRepository('SogedialSiteBundle:ClientProduitMOQ')->getQMinFromCodeClientAndEan13Produit($codeClient, $ean13Produit);

        if (!is_null($qMinArray)) {
            $qMin = $qMinArray['quantiteMinimale'];
        } else {
            $qMin = null;
        }

        $paramViews = array(
            "qMin" => $qMin,
        );

        return $this->render('SogedialIntegrationBundle:Admin:moq-card-product-qmin.html.twig', $paramViews);
    }

    //code mort
    public function catalogueAction()
    {
        $em = $this->getDoctrine()->getManager();

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Catalogue produits');

        $paramViews = array(
            'commercialInfo' => $this->commercialInfo(),
            'products' => $em->getRepository('SogedialSiteBundle:Produit')->getCatalogueAllProduitToAdmin('sec', 20),
            'families' => $this->get('sogedial_integration.catalogue')->getSidebarElementToAdmin(),
            'baseUrl' => $this->container->getParameter('baseUrl'),
            "societe" => $this->getUser()->getEntreprise()->getCode()
        );
        return $this->render('SogedialIntegrationBundle:Admin:catalogue.cc.html.twig', $paramViews);
    }

    //code mort
    public function catalogueWithoutPhotoAction()
    {
        $em = $this->getDoctrine()->getManager();

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Catalogue produits');

        $paramViews = array(
            'commercialInfo' => $this->commercialInfo(),
            'products' => $em->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutPhoto(100),
            'families' => $this->get('sogedial_integration.catalogue')->getSidebarElementToAdmin(),
            'baseUrl' => $this->container->getParameter('baseUrl')
        );

        return $this->render('SogedialIntegrationBundle:Admin:catalogue.cc.html.twig', $paramViews);
    }

    /**
     * @return mixed
     */
    public function getCaForSuperAdminAction()
    {
        $viewParams = array(
            'caValue' => $this->get('sogedial_integration.catalogue')->getCatalogueCa()
        );

        return $this->render('SogedialIntegrationBundle:Admin:catalogue-ca.html.twig', $viewParams);
    }

    public function mesZonesAction()
    {
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise()) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        $em = $this->getDoctrine()->getManager();

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Mes zones');

        $viewParams = array(
            'commercialInfo' => $this->commercialInfo(),
            'mesZones' => $em->getRepository('SogedialSiteBundle:Zone')->getListZones($code_entreprise),
        );
        return $this->render('SogedialIntegrationBundle:Admin:meszones.html.twig', $viewParams);
    }

    public function obtenirSuggestionsAction(Request $request)
    {
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $query = array();
        $query['search'] = $request->get('q');
        $searchService = $this->get('sogedial.recherche_clients');
        $searchService->setCodeEntreprise($code_entreprise);

        return new JsonResponse(array(
                'query' => $query['search'],
                'items' => $searchService->getSuggestions($query, $code_entreprise)
            )
        );
    }

    public function ficheZoneAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $paramViews = array(
            'commercialInfo' => $this->commercialInfo(),
            "zone" => $em->getRepository('SogedialSiteBundle:Zone')->findOneBy(["code" => $id])
        );
        return $this->render('SogedialIntegrationBundle:Admin:zone.html.twig', $paramViews);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function uploadCcvPdfAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $now = new \DateTime();
        $form = $this->get('form.factory')->createNamedBuilder('form', new UploadPdfType())->getForm();
        $oldFile = sprintf('%s/%s', $this->container->getParameter('cgv_upload_dir'), 'SOF_CCV_2017.pdf');

        if (($request->getMethod() == 'POST') && ($request->request->has('form'))) {

            $form->handleRequest($request);
            $file = $form['attachment']->getData();

            if ($form->isValid() && $file !== null) {
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }

                $filename = sprintf('%s/%s_%s_%s.%s', $this->container->getParameter('cgv_upload_dir'), $this->get('sogedial.multisite')->getTrigram(), 'CCV', $now->format('Y'), 'pdf');

                rename($file, $filename);
                chmod($filename, 0777);
                $em->getRepository('SogedialUserBundle:User')->updateUserCcvStatus();
            }
        }

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addItem('Mise à jour des CCV');

        $paramsView = array(
            'form' => $form->createView(),
            'commercialInfo' => $this->commercialInfo()
        );

        return $this->render('SogedialIntegrationBundle:Admin:upload-ccv-pdf.html.twig', $paramsView);
    }


    public function treeAction($user_id)
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();
        $families = array();
        $is_segment = FALSE;
        $id_segment = null;
        $response = new JsonResponse();

        // Header filter
        $pageBuilder = new PageBuilder($em, $this->get('router'), $this->get('translator'), $this->getUser(), $request->query);

        if ($request->query->has('node')) {
            $node = $request->query->get('node');
            $nodeParts = explode('-', $node);
            $parent_id = (!empty($nodeParts[1]) && is_numeric($nodeParts[1])) ? intval($nodeParts[1]) : '';
            $family = $em->getRepository('SogedialSiteBundle:Famille')->findOneBy(array('id' => $parent_id));
            $is_segment = ($family->getType() === 5) ? TRUE : FALSE;
            $id_segment = $family->getId();

            if (!$is_segment) {
                $families = $pageBuilder->buildFamiliesAssortiment($user_id, $family);
            }
        } else {
            $families = $pageBuilder->buildFamiliesAssortiment($user_id);
        }

        foreach ($families as $family) {
            $nb = $family[1];
            $nbAssort = $family[2] + $family[3] + $family[4] + $family[5] + $family[6];
            $family = $family[0];
            $familyId = $family->getId();
            $familyType = $family->getType();
            $assortiments = $family->filterAssortiment($user_id);
            if (count($assortiments) >= 1) {
                $selection = $assortiments[0];
                $checked = true;
                $prix = $selection->getShowPrice();
                $coeff = $selection->getCoefficient();
                $nouv = $selection->getIsNew();
                $promo = $selection->getShowPromotion();
                $exclu = $selection->getShowExclusivity();
            } else {
                $checked = false;
                $prix = false;
                $coeff = null;
                $nouv = false;
                $promo = false;
                $exclu = false;
            }

            $selected = $em->getRepository('SogedialSiteBundle:Famille')->isSelectedFamily($family, $user_id);
            $nbSelect = $pageBuilder->countProductAssortiment($family, $user_id);
            //$nbSelect = $em->getRepository('SogedialSiteBundle:Produit')->countProductAssortiment($family, $user_id);

            $loadOnDemand = ($familyType > 5) ? false : true;
            $tree[] = array(
                'label' => sprintf('%s (%s/%s)', ucfirst(strtolower($family->getLibelleFamille())), $nbSelect[1], $nb),
                'id' => 'family-' . $familyId,
                'entity_id' => $familyId,
                'entity_type' => 'family',
                'load_on_demand' => $loadOnDemand,
                'selected' => $selected,
                'nb_assortiment' => $nbAssort,
                'checked' => $checked,
                'prix' => $prix,
                'coeff' => $coeff,
                'nouv' => $nouv,
                'promo' => $promo,
                'exclu' => $exclu,
                'user_id' => $user_id,
            );
        }

        if ($is_segment) {
            if ($pageBuilder->getQuerySearch() != '') {
                $pageBuilder->setQuerySearch($pageBuilder->getQuerySearch() . ',' . $id_segment);
            } else {
                $pageBuilder->setQuerySearch($id_segment);
            }
            $products = $pageBuilder->buildProduct(true);
            foreach ($products as $product) {
                $assortiments = $product->filterAssortiment($user_id);
                if (count($assortiments) >= 1) {
                    $selection = $assortiments[0];
                    $checked = true;
                    $prix = $selection->getShowPrice();
                    $coeff = $selection->getCoefficient();
                    $nouv = $selection->getIsNew();
                    $promo = $selection->getShowPromotion();
                    $exclu = $selection->getShowExclusivity();
                } else {
                    $checked = false;
                    $prix = false;
                    $coeff = null;
                    $nouv = false;
                    $promo = false;
                    $exclu = false;
                }

                $productId = $product->getId();
                $tree[] = array(
                    'label' => sprintf('%s', $product->getDenominationCourt()),
                    'id' => 'product-' . $productId,
                    'entity_id' => $productId,
                    'entity_type' => 'product',
                    'load_on_demand' => false,
                    'selected' => false,
                    'checked' => $checked,
                    'prix' => $prix,
                    'coeff' => $coeff,
                    'nouv' => $nouv,
                    'promo' => $promo,
                    'exclu' => $exclu,
                    'user_id' => $user_id,
                );
            }
        }

        $response->setData($tree);
        return $response;
    }

    public function indexAction()
    {
        return $this->render('SogedialSiteBundle:Admin:index.html.twig');
    }

    public function productManagerAction(Request $request)
    {
        $format = $request->getRequestFormat();

        $pageBuilder = new PageBuilder($this->getDoctrine()->getManager(), $this->get('router'), $this->get('translator'), $this->getUser(), $request->query);

        $products = $pageBuilder->buildProduct();
        $nbProduct = $pageBuilder->buildNbProduct();
        $filter = $pageBuilder->buildVisibleFilter();
        $bornes = $pageBuilder->buildMinAndMax();

        $rayon = $pageBuilder->buildFamilies(1, false);
        $families = $pageBuilder->buildFamilies(2, false);
        $subfamilies = $pageBuilder->buildFamilies(3, false);

        $selectedProducts = $this->getDoctrine()
            ->getRepository('SogedialSiteBundle:UserSelectedProduct')
            ->getAllSelectedProductsIdByUserId($this->getUser()->getId());

        $selectedProductsId = [];

        foreach ($selectedProducts as $selectedProduct) {
            $selectedProductsId[] = $selectedProduct['id'];
        }


        // Extraction PDF
        if ($format == 'pdf') {
            $webPath = dirname($this->get('kernel')->getRootDir()) . '/web';

            // Creation des jobs
            $filepath = '/pdf/sogedial-' . time();
            $optionsJobCreateHTML = array(
                "--user-id=" . $this->getUser()->getId(),
                "--dst-filepath=" . $webPath . $filepath . ".html",
            );
            foreach ($request->query->all() as $param => $value) {
                $optionsJobCreateHTML[] = "--query=" . $param . "::" . $value . "";
            }
            $jobCreateHTML = new \JMS\JobQueueBundle\Entity\Job('sogedial-products:createHTML', $optionsJobCreateHTML);

            $optionsJobCreatePDF = array(
                "--src-filepath=" . $webPath . $filepath . ".html",
                "--dst-filepath=" . $webPath . $filepath . ".pdf",
            );
            $jobCreatePDF = new \JMS\JobQueueBundle\Entity\Job('sogedial-products:createPDF', $optionsJobCreatePDF);
            $jobCreatePDF->addDependency($jobCreateHTML);

            $optionsJobSendMail = array(
                "--user-id=" . $this->getUser()->getId(),
                "--filepath=" . $webPath . $filepath . ".pdf",
            );
            $jobSendMail = new \JMS\JobQueueBundle\Entity\Job('sogedial-products:sendMail', $optionsJobSendMail);
            $jobSendMail->addDependency($jobCreatePDF);
            $em = $this->getDoctrine()->getManager();
            $em->persist($jobCreateHTML);
            $em->persist($jobCreatePDF);
            $em->persist($jobSendMail);
            $em->flush($jobCreateHTML);


            // message a retourner à l'utilisateur
            $this->get('session')->getFlashBag()->add(
                'notice', 'Votre demande pour exporter la liste des produits au format PDF a été prise en compte.
                Vous aller recevoir un mail avec le document en pièce jointe rapidement.'
            );

            // default page
            return $this->render('SogedialSiteBundle:Site:products.html.twig', array(
                'products' => $products,
                'rayon' => $rayon,
                'families' => $families,
                'subfamilies' => $subfamilies,
                'admin' => false,
                'nbProduct' => $nbProduct,
                'filter' => $filter,
                'admin' => true,
                'bornes' => $bornes,
                'selectedProductsId' => $selectedProductsId));
        } // Extraction EXCEL
        elseif ($format == 'xls') {
            $response = $this->buildExcel($pageBuilder);
            return $response;
        } // Réponse normale
        else {
            return $this->render('SogedialSiteBundle:Site:products.html.twig', array(
                'products' => $products,
                'rayon' => $rayon,
                'families' => $families,
                'subfamilies' => $subfamilies,
                'admin' => false,
                'nbProduct' => $nbProduct,
                'filter' => $filter,
                'admin' => true,
                'bornes' => $bornes,
                'selectedProductsId' => $selectedProductsId));
        }
    }

    public function scrollProductAction(Request $request, $offset)
    {
        $pageBuilder = new PageBuilder($this->getDoctrine()->getManager(), $this->get('router'), $this->get('translator'), $this->getUser(), $request->query);
        $products = $pageBuilder->buildProduct(false, $offset);

        return $this->render('SogedialSiteBundle:Product:listProduct.html.twig', array('products' => $products, 'admin' => true));
    }

    /**
     *
     * @ParamConverter("product", class="SogedialSiteBundle:Produit")
     * @return type
     */
    public function productManagerDetailAction(Produit $product, Request $request)
    {
        $pageBuilder = new PageBuilder($this->getDoctrine()->getManager(), $this->get('router'), $this->get('translator'), $this->getUser(), $request->query);
        $em = $this->getDoctrine()->getManager();

        $rayon = $pageBuilder->buildFamilies(1);
        $families = $pageBuilder->buildFamilies(2);
        $subfamilies = $pageBuilder->buildFamilies(3);
        $filter = $pageBuilder->buildVisibleFilter();
        $bornes = $pageBuilder->buildMinAndMax();

        $selectedProducts = $this->getDoctrine()
            ->getRepository('SogedialSiteBundle:UserSelectedProduct')
            ->getAllSelectedProductsIdByUserId($this->getUser()->getId());

        $selectedProductsId = [];

        foreach ($selectedProducts as $selectedProduct) {
            $selectedProductsId[] = $selectedProduct['id'];
        }

        $product = $pageBuilder->buildSingleProduct($product);
        $formImage = $this->get('form.factory')->createNamedBuilder('formImage', new ProduitImageType(), $product)->getForm();
        $formFile = $this->get('form.factory')->createNamedBuilder('formFile', new ProduitFileType(), $product)->getForm();
        $nbProduct = array('total' => 1);
        $docs = $this->loadFiles($product);

        if ($request->getMethod() == 'POST') {
            if ($request->request->has('formImage')) {
                $formImage->bind($request);
                $file = $formImage['attachment']->getData();
                if ($formImage->isValid() && $file != null) {
                    $dir = $this->get('kernel')->getRootDir() . '/../web/images/product/original/' . $product->getEan13() . '/';
                    $file->move($dir, $file->getClientOriginalName());
                    $photo = new Photo();
                    $photo->setCover(0);
                    $photo->setDisplay(0);
                    $photo->setLibelle($file->getClientOriginalName());
                    $photo->setSource($product->getEan13() . '/' . $file->getClientOriginalName());
                    $product->addPhoto($photo);
                    $em->persist($product);
                    $em->flush();
                }
            } elseif ($request->request->has('formFile')) {
                $formFile->bind($request);
                $file = $formFile['attachment']->getData();
                if ($formFile->isValid() && $file != null) {
                    $dir = $this->get('kernel')->getRootDir() . '/../web/images/product/original/' . $product->getEan13() . '/docs/';
                    $prefix = $formFile->get('type')->getData();
                    $file->move($dir, $prefix . '__' . $file->getClientOriginalName());
                    $docs[] = array(
                        'type' => $prefix,
                        'label' => $file->getClientOriginalName(),
                        'path' => $product->getEan13() . '/docs/' . $prefix . '__' . $file->getClientOriginalName(),
                        'delete' => $prefix . '__' . $file->getClientOriginalName(),
                    );
                }
            }

            return $this->render('SogedialSiteBundle:Admin:productManagerDetail.html.twig', array(
                'product' => $product,
                'rayon' => $rayon,
                'families' => $families,
                'subfamilies' => $subfamilies,
                'filter' => $filter,
                'admin' => false,
                'nbProduct' => $nbProduct,
                'formImage' => $formImage->createView(),
                'formFile' => $formFile->createView(),
                'bornes' => $bornes,
                'selectedProductsId' => $selectedProductsId,
                'docs' => $docs,
            ));
        }

        return $this->render('SogedialSiteBundle:Admin:productManagerDetail.html.twig', array(
            'product' => $product,
            'rayon' => $rayon,
            'families' => $families,
            'subfamilies' => $subfamilies,
            'filter' => $filter,
            'admin' => false,
            'nbProduct' => $nbProduct,
            'formImage' => $formImage->createView(),
            'formFile' => $formFile->createView(),
            'bornes' => $bornes,
            'selectedProductsId' => $selectedProductsId,
            'docs' => $docs,
        ));
    }

    private function loadFiles(Produit $product)
    {
        $dir = $this->get('kernel')->getRootDir() . '/../web/images/product/original/' . $product->getEan13() . '/docs/';
        $docs = array();
        $MyDirectory = opendir($dir) or array();
        $labels = array(
            'technique' => 'Fiche technique',
            'certificat' => 'Certificat',
            'argumentaire' => 'Argumentaire de vente',
        );

        while ($Entry = @readdir($MyDirectory)) {
            if ($Entry != '.' && $Entry != '..') {
                $pattern = '$(?<type>\w+)__(?<label>.+)$';
                $result = array();
                preg_match($pattern, $Entry, $result);
                $file = array(
                    'type' => (isset($result['type'])) ? $labels[$result['type']] : 'Non défini',
                    'label' => (isset($result['label'])) ? $result['label'] : $Entry,
                    'path' => $product->getEan13() . '/docs/' . $Entry,
                    'delete' => $Entry,
                );

                $docs[] = $file;
            }
        }

        return $docs;
    }

    public function validateTranslationAction(Produit $product, $locale, Request $request)
    {
        $product->addTranslationValidation($locale);
        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();
        $response = new JsonResponse();
        $response->setData(array('validate' => $locale));
        return $response;
    }

    public function unvalidateTranslationAction(Produit $product, $locale, Request $request)
    {
        $product->removeTranslationValidation($locale);
        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();
        $response = new JsonResponse();
        $response->setData(array('validate' => $locale));
        return $response;
    }

    public function accessUserManagerAction()
    {
        $translator = $this->get('translator');
        $service = $this->container->get('sogedial_site.useraccess');
        $states = array('pending', 'validate', 'lock', 'deny');
        $blocks = array();

        foreach ($states as $state) {
            $blocks[$state] = $service->getParametersBlockByState($state);
        }

        $params = array(
            'blocks' => $blocks
        );
        return $this->render('SogedialSiteBundle:Admin:accessUserManager.html.twig', $params);
    }

    public function accessUserManagerByStateAction($state)
    {
        $service = $this->container->get('sogedial_site.useraccess');
        $data = $service->getElementsByState($state);

        // Les actions...
        foreach ($data as $key => $element) {
            $tmp = array();

            if ($state == 'validate') {
                $tmp['userId'] = $element->getId();
                $tmp['actions'] = array(
                    'permissions' => $this->generateUrl('SogedialSite_accessProductManager', array('id' => $element->getId())),
                    'edit' => $this->generateUrl('SogedialSite_editUser', array('id' => $element->getId())),
                    'delete' => $this->generateUrl('SogedialSite_deleteUser', array('id' => $element->getId())),
                );
                $tmp['updated']['date'] = $element->getUpdated()->format('Y-m-d');
                $tmp['paysVente'] = $element->getPaysVente();
                $tmp['chiffreAffaire'] = $element->getChiffreAffaire();
                $tmp['prenom'] = $element->getPrenom();
                $tmp['nom'] = $element->getNom();
                $tmp['login'] = $element->getUsername();
                $tmp['age'] = round((time() - strtotime($element->getCreated()->format('Y-m-d H:i:s'))) / (60 * 60 * 24)) . ' jours';
                $tmp['temps'] = round($element->getTotalTime() / 60) . ' minutes';
                $tmp['connexion'] = $element->getCountConnexion() . ' connexions';
                $tmp['derniereConnexion'] = $element->getLastLogin() === null ? " " : $element->getLastLogin()->format('H:i:s d/m/Y ');
                $tmp['commande'] = '0 commandes';

            } else {
                $element['actions'] = array(
                    'permissions' => $this->generateUrl('SogedialSite_accessProductManager', array('id' => $element['id'])),
                    'edit' => $this->generateUrl('SogedialSite_editUser', array('id' => $element['id'])),
                    'delete' => $this->generateUrl('SogedialSite_deleteUser', array('id' => $element['id'])),
                );

                $element['login'] = $element['username'];
                $element['cause'] = '-';
                $tmp = $element;
            }
            $data[$key] = $tmp;
        }

        $response = new JsonResponse();
        $response->setData(array('data' => $data));
        return $response;
    }

    /**
     *
     * @param \Sogedial\UserBundle\Entity\User $user
     * @return type
     */
    public function editUserAction(User $user)
    {
        $form = $this->createForm(new UserEditType, $user);

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $enabled = $user->getEnabled();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                // Entreprise nouvellement créée
                if ($user->getEntreprise() == null) {
                    $entreprise = $form['entreprise_new']->getData();
                    if (is_null($entreprise)) {
                        $errorMsg = "Vous devez sélectionner une entreprise ou en créer une nouvelle";
                        $form->get('entreprise')->addError(new FormError($errorMsg));
                        return $this->render('SogedialSiteBundle:Admin:editUser.html.twig', array('form' => $form->createView(), 'user' => $user));
                    }
                    $user->setEntreprise($entreprise);
                }
                $user->setEnabled($enabled);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add('info', "L'utilisateur à bien été modifié");
                return $this->redirect($this->generateUrl('SogedialSite_accessUserManager'));
            }
        }

        $translator = $this->get('translator');
        $breadcrumb = array(
            array('url' => '#', 'title' => $translator->trans('userManager.accessBreadcrumb', array(), 'SogedialSiteBundle')),
            array('url' => $this->generateUrl('SogedialSite_accessUserManager'), 'title' => $translator->trans('userManager.pagetitle', array(), 'SogedialSiteBundle')),
            array('url' => '#', 'title' => $translator->trans('userManager.edituser.pagetitle', array('%identifier%' => $user->getId()), 'SogedialSiteBundle'))
        );
        $statusList = $user->getAvailableStatus();
        $params = array(
            'form' => $form->createView(),
            'user' => $user,
            'statusList' => $statusList,
            'breadcrumb' => $breadcrumb
        );

        return $this->render('SogedialSiteBundle:Admin:editUser.html.twig', $params);
    }

    /**
     *
     * @param \Sogedial\UserBundle\Entity\User $user
     * @param type $status
     * @return type
     */
    public function editUserStatusAction(User $user, $status)
    {
        $statusList = $user->getAvailableStatus();
        $statusExists = in_array($status, $statusList);
        if ($statusExists) {
            $user->setStatut($status);
        }
        $form = $this->createForm(new UserEditStatusType, $user);
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                switch ($status) {
                    case 'pending':
                        $user->setEnabled(false);
                        $user->setLocked(false);
                        break;
                    case 'validate':
                        $user->setEnabled(true);
                        break;
                    case 'lock':
                    case 'deny':
                        $user->setLocked(true);
                        break;
                    default:
                        break;
                }
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add('info', "L'utilisateur à bien été modifié");
                return $this->redirect($this->generateUrl('SogedialSite_accessUserManager'));
            }
        }

        $params = array(
            'status' => $status,
            'statusExists' => $statusExists,
            'form' => $form->createView(),
            'user' => $user
        );
        return $this->render('SogedialSiteBundle:Admin:editUserStatus.html.twig', $params);
    }

    public function addUserAction()
    {
        $user = new User;
        $form = $this->createForm(new UserType, $user);
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $translator = $this->get('translator');
        $breadcrumb = array(
            array('url' => '#', 'title' => $translator->trans('userManager.accessBreadcrumb', array(), 'SogedialSiteBundle')),
            array('url' => $this->generateUrl('SogedialSite_accessUserManager'), 'title' => $translator->trans('userManager.pagetitle', array(), 'SogedialSiteBundle')),
            array('url' => '#', 'title' => $translator->trans('userManager.adduser.breadcrumb', array(), 'SogedialSiteBundle'))
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // Encodage du mot de passe utilisateur
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);

                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                $user->setPassword($password);
                // Entreprise nouvellement créée
                if ($user->getEntreprise() == null) {
                    $entreprise = $form['entreprise_new']->getData();
                    if (is_null($entreprise)) {
                        $errorMsg = "Vous devez sélectionner une entreprise ou en créer une nouvelle";
                        $form->get('entreprise')->addError(new FormError($errorMsg));
                        return $this->render('SogedialSiteBundle:Admin:addUser.html.twig', array('form' => $form->createView(), 'user' => $user, 'breadcrumb' => $breadcrumb));
                    }
                    $user->setEntreprise($entreprise);
                }

                if ($request->request->has('save-pending')) {
                    $user->setStatut('pending');
                    $user->setEnabled(false);
                } elseif ($request->request->has('save-validate')) {
                    $user->setStatut('validate');
                    $user->setEnabled(true);
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add('info', "L'utilisateur à bien été ajouté");
                return $this->redirect($this->generateUrl('SogedialSite_accessUserManager'));
            }
        }
        return $this->render('SogedialSiteBundle:Admin:addUser.html.twig', array('form' => $form->createView(), 'user' => $user, 'breadcrumb' => $breadcrumb));
    }

    public function addClientAccessAction()
    {
        $user = new User;
        $form = $this->createForm(new UserTypeClient, $user);
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $translator = $this->get('translator');

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // Encodage du mot de passe utilisateur
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);

                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                $user->setPassword($password);
                // Entreprise nouvellement créée
                if ($user->getEntreprise() == null) {
                    $entreprise = $form['entreprise_new']->getData();
                    if (is_null($entreprise)) {
                        $errorMsg = $translator->trans('flashbag.accesserrorenterprise', array(), 'SogedialSiteBundle');
                        $form->get('entreprise')->addError(new FormError($errorMsg));
                        return $this->render('SogedialSiteBundle:Admin:addUserClient.html.twig', array('form' => $form->createView(), 'user' => $user));
                    }
                    $user->setEntreprise($entreprise);
                }

                $user->setStatut('pending');
                $user->addRole('ROLE_USER');
                $user->setEtat('Prospect');

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->add('info', $translator->trans('flashbag.accessrequest', array(), 'SogedialSiteBundle'));
                return $this->redirect($this->generateUrl('SogedialSite_accueil'));
            }
        }
        return $this->render('SogedialSiteBundle:Admin:addUserClient.html.twig', array('form' => $form->createView(), 'user' => $user));
    }

    /**
     *
     * @param \Sogedial\UserBundle\Entity\User $user
     * @return type
     */
    public function deleteUserAction(User $user)
    {
        // On créé un formulaire vide, qui ne contiendra que le champ CSRF
        $form = $this->createFormBuilder()->getForm();
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // On supprime l'utilsiateur
                $em = $this->getDoctrine()->getManager();
                $userOrders = $em->getRepository('SogedialSiteBundle:Commande')->findByUser($user);
                foreach ($userOrders as $userOrder) {
                    $userOrderStates = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findByOrder($userOrder);
                    $userOrderProducts = $em->getRepository('SogedialSiteBundle:LigneCommande')->findByOrder($userOrder);

                    foreach ($userOrderStates as $userOrderState) {

                        $em->remove($userOrderState);
                    }
                    foreach ($userOrderProducts as $userOrderProduct) {

                        $em->remove($userOrderProduct);
                    }
                    $em->remove($userOrder);
                }

                $emConnection = $em->getConnection();
                $sql = "DELETE FROM user_selectedproduct WHERE user_id = ? ";
                $emConnection->executeQuery(
                    $sql, array($user->getId())
                );
                $em->remove($user);
                $em->flush();

                // On définit un message flash
                $this->get('session')->getFlashBag()->add('info', "L'utilisateur à bien été supprimé");
                return $this->redirect($this->generateUrl('SogedialSite_accessUserManager'));
            }
        }
        // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
        return $this->render('SogedialSiteBundle:Admin:deleteUser.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }

    public function accessProductManagerAction(User $user)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // Header filter
        $pageBuilder = new PageBuilder($this->getDoctrine()->getManager(), $this->get('router'), $this->get('translator'), $this->getUser(), $request->query);

        $nbProduct = $pageBuilder->buildNbProduct();
        $filter = $pageBuilder->buildVisibleFilter();

        $filter['autre']['new'] = false;
        $filter['autre']['promo'] = false;
        $filter['autre']['exclu'] = false;
        // Pas de filtre par prix en admin
        $bornes = array('min' => null, 'max' => null); //$pageBuilder->buildMinAndMax();


        $rayon = $pageBuilder->buildFamilies(1, false);
        $families = $pageBuilder->buildFamilies(2, false);
        $subfamilies = $pageBuilder->buildFamilies(3, false);

        $parameters = array(
            'nbProduct' => $nbProduct,
            'rayon' => $rayon,
            'families' => $families,
            'subfamilies' => $subfamilies,
            'filter' => $filter,
            'bornes' => $bornes
        );

        // Sections user
        $formType = new UserEditSelectionType();
        $form = $this->createForm($formType);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $arg = array();
                $arg['rayon'] = $request->get('rayon') != '' ? explode(',', $request->get('rayon')) : array();
                $arg['familie'] = $request->get('famille') != '' ? explode(',', $request->get('famille')) : array();
                $arg['sfamilie'] = $request->get('sfamille') != '' ? explode(',', $request->get('sfamille')) : array();
                $arg['marque'] = $request->get('marque') != '' ? explode(',', urldecode($request->get('marque'))) : array();

                $arg['search'] = $request->get('search') != '' ? explode(',', urldecode($request->get('search'))) : array();
                $arg['gamme'] = $request->get('gamme') != '' ? explode(',', $request->get('gamme')) : array();
                $arg['temp'] = $request->get('temp') != '' ? explode(',', $request->get('temp')) : array();
                $arg['autre'] = $request->get('autre') != '' ? explode(',', $request->get('autre')) : array();
                $arg['photo'] = $request->get('photo');
                $filter = false;
                // Un filtre est actif
                if ($arg['familie'] || $arg['sfamilie'] || $arg['marque'] ||
                    $arg['search'] || $arg['gamme'] || $arg['temp'] || $arg['autre'] || $arg['photo']
                ) {
                    $filter = true;
                }
                /* $user->setFamilySelections(new ArrayCollection($em->getRepository('SogedialUserBundle:FamilySelection')->findBy(array('user' => $user))));
                  $user->setProductSelections(new ArrayCollection($em->getRepository('SogedialUserBundle:ProductSelection')->findBy(array('user' => $user))));
                 */

                if ($filter) {
                    $selections = $request->request->get('sogedial_userbundle_usereditselections', array());
                    $famillySelections = isset($selections['family_selections']) ? $selections['family_selections'] : array();
                    $productSelections = isset($selections['product_selections']) ? $selections['product_selections'] : array();

                    foreach ($famillySelections as $selection) {
                        $family = $em->getRepository('SogedialSiteBundle:Famille')->find($selection['entity_id']);
                        if ($family) {
                            $tmp = new FamilySelection();
                            $tmp->setUser($user);
                            $tmp->setShowPrice(isset($selection['show_price']) ? $selection['show_price'] : false);
                            $tmp->setShowPromotion(isset($selection['show_promotion']) ? $selection['show_promotion'] : false);
                            $tmp->setShowExclusivity(isset($selection['show_exclusivity']) ? $selection['show_exclusivity'] : false);
                            $tmp->setCoefficient(!empty($selection['coefficient']) ? $selection['coefficient'] : 1);
                            $tmp->setIsNew(isset($selection['is_new']) ? $selection['is_new'] : false);
                            $tmp->setEntity($family);

                            if (isset($selection['checked']) && $selection['checked'] == true) {
                                $em->getRepository('SogedialUserBundle:ProductSelection')->addProductSelectionFilter($user->getId(), $tmp, $arg);
                            }
                            $user->removeFamilySelection($tmp);
                        }
                    }

                    foreach ($productSelections as $selection) {
                        $product = $em->getRepository('SogedialSiteBundle:Produit')->find($selection['entity_id']);
                        if ($product) {
                            if (isset($selection['checked']) && $selection['checked'] == true) {
                                $tmp = new ProductSelection();
                                $tmp->setUser($user);
                                $tmp->setShowPrice(isset($selection['show_price']) ? $selection['show_price'] : false);
                                $tmp->setShowPromotion(isset($selection['show_promotion']) ? $selection['show_promotion'] : false);
                                $tmp->setShowExclusivity(isset($selection['show_exclusivity']) ? $selection['show_exclusivity'] : false);
                                $tmp->setCoefficient(!empty($selection['coefficient']) ? $selection['coefficient'] : 1);
                                $tmp->setIsNew(isset($selection['is_new']) ? $selection['is_new'] : false);
                                $tmp->setEntity($product);
                                $user->addProductSelection($tmp);
                            } else {
                                $tmp = new ProductSelection();
                                $tmp->setUser($user);
                                $tmp->setEntity($product);
                                $user->removeProductSelection($tmp);
                            }
                        }
                    }

                    $em->persist($user);
                    $em->flush();
                } else {
                    $selections = $request->request->get('sogedial_userbundle_usereditselections', array());
                    $famillySelections = isset($selections['family_selections']) ? $selections['family_selections'] : array();
                    $productSelections = isset($selections['product_selections']) ? $selections['product_selections'] : array();

                    foreach ($famillySelections as $selection) {
                        $family = $em->getRepository('SogedialSiteBundle:Famille')->find($selection['entity_id']);
                        if ($family) {
                            if (isset($selection['checked']) && $selection['checked'] == true) {
                                $tmp = new FamilySelection();
                                $tmp->setUser($user);
                                $tmp->setShowPrice(isset($selection['show_price']) ? $selection['show_price'] : false);
                                $tmp->setShowPromotion(isset($selection['show_promotion']) ? $selection['show_promotion'] : false);
                                $tmp->setShowExclusivity(isset($selection['show_exclusivity']) ? $selection['show_exclusivity'] : false);
                                $tmp->setCoefficient(!empty($selection['coefficient']) ? $selection['coefficient'] : 1);
                                $tmp->setIsNew(isset($selection['is_new']) ? $selection['is_new'] : false);
                                $tmp->setEntity($family);
                                $tmp->entity_id = $family->getId();
                                $tmp->user_id = $user->getId();
                                $user->addFamilySelection($tmp);
                            } else {
                                $tmp = new FamilySelection();
                                $tmp->setUser($user);
                                $tmp->setEntity($family);
                                $tmp->entity_id = $family->getId();
                                $tmp->user_id = $user->getId();
                                $user->removeFamilySelection($tmp);
                            }
                        }
                    }

                    foreach ($productSelections as $selection) {
                        $product = $em->getRepository('SogedialSiteBundle:Produit')->find($selection['entity_id']);
                        if ($product) {
                            if (isset($selection['checked']) && $selection['checked'] == true) {
                                $tmp = new ProductSelection();
                                $tmp->setUser($user);
                                $tmp->setShowPrice(isset($selection['show_price']) ? $selection['show_price'] : false);
                                $tmp->setShowPromotion(isset($selection['show_promotion']) ? $selection['show_promotion'] : false);
                                $tmp->setShowExclusivity(isset($selection['show_exclusivity']) ? $selection['show_exclusivity'] : false);
                                $tmp->setCoefficient(!empty($selection['coefficient']) ? $selection['coefficient'] : 1);
                                $tmp->setIsNew(isset($selection['is_new']) ? $selection['is_new'] : false);
                                $tmp->setEntity($product);
                                $user->addProductSelection($tmp);
                            } else {
                                $tmp = new ProductSelection();
                                $tmp->setUser($user);
                                $tmp->setEntity($product);
                                $user->removeProductSelection($tmp);
                            }
                        }
                    }

                    $em->persist($user);
                    $em->flush();
                }

                $params = array(
                    'form' => $form->createView(),
                    'user' => $user
                );
                $parameters = $parameters + $params;

                $this->get('session')->getFlashBag()->add('info', "Mise a jour du catalogue de utilisateur");
                return $this->redirect($this->generateUrl('SogedialSite_accessProductManager', array('id' => $user->getId())));
            }
        }

        $params = array(
            'form' => $form->createView(),
            'user' => $user
        );
        $parameters = $parameters + $params;

        return $this->render('SogedialSiteBundle:Admin:accessProductManager.html.twig', $parameters);
    }

    public function deleteAssortimentFromFamilyAction(Request $request)
    {
        $user_id = $request->query->get('user_id');
        $family_id = $request->query->get('family_id');
        $em = $this->getDoctrine()->getManager();

        $em->getRepository('SogedialUserBundle:FamilySelection')->deleteFamilyFromUser($user_id, $family_id);
        $em->getRepository('SogedialUserBundle:ProductSelection')->deleteFamilyFromUser($user_id, $family_id);

        return $this->redirect($this->generateUrl('SogedialSite_accessProductManager', array('id' => $user_id)));
    }

    public function searchListJsonAction()
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('SogedialUserBundle:User')
            ->findAllUserJson();

        $data = array();
        $invalidChar = array('(', ')', '*', '?', '+', ',');
        foreach ($users as $user) {
            $data[] = array('id' => $user->getId(), 'name' => strtolower(str_replace($invalidChar, '', $user->getUsername())));
        }

        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function deleteFileAction(Produit $product, $file)
    {
        unlink($this->get('kernel')->getRootDir() . '/../web/images/product/original/' . $product->getEan13() . '/docs/' . $file);

        return $this->redirect($this->generateUrl('SogedialSite_productManagerDetail', array(
            'id' => $product->getId(),
        )));
    }

    /**
     * @return Response
     */
    public function manageUsersAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:list-utilisateur.html.twig', $viewParams);
    }

    public function clientsListJsonAction()
    {
        $clients = $this
            ->getDoctrine()
            ->getRepository('SogedialUserBundle:User')
            ->getListClient();

        return new JsonResponse(['data' => $clients]);
    }

    public function clientsWithAccessJsonAction()
    {
        $clients = $this
            ->getDoctrine()
            ->getRepository('SogedialUserBundle:User')
            ->getListClientWithAccess();

        return new JsonResponse(['data' => $clients]);
    }

    public function listClientsWithAccessAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:clients-with-access.html.twig', $viewParams);
    }

    public function listClientsWithoutAccessAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:clients-without-access.html.twig', $viewParams);
    }

    public function assortimentsClientSelectAction($id, $valeur)
    {
        $this->get("sogedial.assortimentclient")->chooseAssortimentClient($id, $valeur);
        $url = $this->generateUrl('sogedial_integration_admin_client_assortiments', ["id" => $id]);
        return $this->redirect($url);
    }

    /**
     * @param $codeZone
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editZoneAction($codeZone, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $zone = $em->getRepository('SogedialSiteBundle:Zone')->findOneBy(["code" => $codeZone]);
        $form = $this->createForm(new AddZoneAccessType(), $zone);
        $form->setData($zone);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $formData = $form->getData();
            if ($formData->getNom()) {
                $zone->setNom($formData->getNom());
            }
            $zone->setLundi($formData->getLundi());
            $zone->setMardi($formData->getMardi());
            $zone->setMercredi($formData->getMercredi());
            $zone->setJeudi($formData->getJeudi());
            $zone->setVendredi($formData->getVendredi());
            $zone->setSamedi($formData->getSamedi());
            $zone->setDimanche($formData->getDimanche());
            $em->persist($zone);
            $em->flush();
            return $this->redirect($this->generateUrl('sogedial_integration_admin_meszones'));
        }


        $paramViews = array(
            'commercialInfo' => $this->commercialInfo(),
            'form' => $form->createView(),
            "zone" => $zone
        );

        return $this->render('SogedialIntegrationBundle:Admin:ajouter-zone.html.twig', $paramViews);
    }

    /**
     * @param $codeClient
     * @param Request $request
     * @param string step Name of the target step.
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editClientAction($codeClient, Request $request, $step, $mode)
    {
        if ($step === 'null') {
            return $this->redirect($this->generateUrl('SogedialSite_admin_list_users'));
        }
        $em = $this->getDoctrine()->getManager();

        // Repositories
        $ClientRepository = $em->getRepository('SogedialSiteBundle:Client');
        $CommandeRepository = $em->getRepository('SogedialSiteBundle:Commande');
        $EntrepriseRepository = $em->getRepository('SogedialSiteBundle:Entreprise');
        $MetaClientRepository = $em->getRepository('SogedialSiteBundle:MetaClient');
        $ProduitRepository = $em->getRepository('SogedialSiteBundle:Produit');
        $UserRepository = $em->getRepository('SogedialUserBundle:User');
        $ZoneRepository = $em->getRepository('SogedialSiteBundle:Zone');

        // Services
        $multiSiteService = $this->get('sogedial.multisite');

        // If mode wasn't defined in the query string, retrieve it with its default value.
        $queriedMode = $request->query->get('mode');
        $mode = ($queriedMode ?: $mode);

        // Misc.
        $codeEntreprise = substr($codeClient, 0, strpos($codeClient, '-'));
        $userObj = null;
        $client = $ClientRepository->findOneByCode($codeClient);
        $metaClients = $MetaClientRepository->getMetaFilter($codeClient);
        $emailFallbackBase = $multiSiteService->getEmailFallbackBase();
        $email = (empty($client->getEmail())) ? $codeClient . $emailFallbackBase : $client->getEmail();
        $session = $request->getSession();
        $clientEditRoute = 'SogedialSite_ajout_client';
        // For non-Sogedial clients, prevent enseigne and assortiment modifications.
        $isSogedialClient = ($client->getRegion()->getCode() === '4');
        // TODO: remove all conditions on geocoding once it will be permanently activated.
        $isGeocodingEnabled = false;
        $steps = [
            'login-password' => [
                'id' => 1,
                'nextStep' => 'general',
                'enabled' => true,
            ],
            'general' => [
                'id' => 2,
                'nextStep' => ($isSogedialClient
                    ? 'enseigne-assortiment'
                    : ($isGeocodingEnabled ? 'geocoding' : 'null')
                ),
                'enabled' => true,
            ],
            'enseigne-assortiment' => [
                'id' => 3,
                'nextStep' => ($isGeocodingEnabled ? 'geocoding' : 'null'),
                'enabled' => $isSogedialClient,
            ],
            'geocoding' => [
                'id' => 4,
                'nextStep' => 'null',
                'enabled' => $isGeocodingEnabled,
            ],
        ];
        $paramViews = [];

        if ($client->getMeta() !== null) {
            $userObj = $UserRepository->findOneByMeta($client->getMeta()->getCode());
        }
        $societe_site = $multiSiteService->getSociete();
        $entrepriseObject = $EntrepriseRepository->findOneByCode($societe_site);
        $entrepriseCourante = $EntrepriseRepository->findOneByCode($codeEntreprise);

        switch ($step) {
            case 'login-password':
                $credentialsClient = new CredentialsType();
                $addClient = new AddClientAccessType();
                $credentialsForm = $this->createForm($credentialsClient, $userObj);
                $metaClientForm = $this->createForm($addClient, $userObj);

                if ($userObj instanceof User) {
                    $metaClientForm->setData($userObj);
                    $credentialsForm->setData($userObj);
                }

                $metaClientForm->handleRequest($request);
                $formData = $metaClientForm->getData();
                $credentialsForm->handleRequest($request);
                $formDataCredentials = $credentialsForm->getData();
                if ($metaClientForm->isValid() && ($client instanceof Client) && $request->request->get('client_access') !== null) {
                    $meta_client_choose_code = $request->request->get('meta_client_choose');
                    if ($meta_client_choose_code === 'new') {
                        if (trim($formData['username']) !== "" && trim($formData['password']) !== "") {
                            $meta = new MetaClient();
                            $meta->setCode(sprintf('%s-%s', 'Ecom', sprintf("%09d", rand())));
                            $meta->setLibelle($client->getNom());
                            $em->persist($meta);
                            $em->flush();
                        } else {
                            $metaError = new FormError("Vous devez saisir un identifiant et un mot de passe de compte pour votre client.");
                            $metaClientForm->addError($metaError);
                        }
                    } elseif ($meta_client_choose_code !== NULL) {
                        $meta = $MetaClientRepository->findOneByCode($meta_client_choose_code);
                    } else {
                        $metaError = new FormError("Vous devez sélectionner un compte existant ou en créer un nouveau");
                        $metaClientForm->addError($metaError);
                        $meta = NULL;
                    }

                    if ($userObj instanceof User) {
                        if ($meta_client_choose_code === 'new') {
                            $formData->setUsername($userObj->getUsername());
                            $formData->setPassword($userObj->getPassword());
                            $formData->setMeta($userObj->getMeta());
                            $userObj = new User();
                        }
                        // notez que $formData est dans ce cas de type User et possède donc la méthode getZone()
                        $encoder = $this->container->get('security.password_encoder');
                        $password = $encoder->encodePassword($userObj, $formData->getPassword());

                        $username = strtolower(substr($codeClient, 4));
                        $formName = $formData->getUsername();
                        if (isset($formName)) {
                            $username = $formName;
                        }
                        $userObj->setUsername($username);
                        $userObj->setPassword($password);
                        $userObj->setEmail($email);
                        //$userObj->setClient($client);
                        $userObj->setEnabled(true);
                        $userObj->setEntreprise($entrepriseObject);

                        if (isset($meta)) {
                            $userObj->setMeta($meta);
                        }
                        $userObj->setEntrepriseCourante($entrepriseCourante->getCode());
                        $userObj->addRole('ROLE_USER');
                        $userObj->setEtat('client');
                        $userObj->setCgvCpvUpdatedAt(new \DateTime('now'));
                        if (isset($meta)) {
                            $client->setMeta($meta);
                        }

                        $em->persist($userObj);
                        $em->flush();
                    } else {
                        if ($client instanceof Client) {
                            if (isset($meta)) {
                                $client->setMeta($meta);
                            }
                            $client->setEActif(true);
                            $em->persist($client);
                            $em->flush();
                        }
                        if ($meta_client_choose_code === 'new') {
                            if (trim($formData['username']) !== "" && trim($formData['password']) !== "") {
                                $user = new User();
                                $user->setUsername($formData['username']);
                                $user->setPlainPassword($formData['password']);
                                $user->setEmail($email);
                                //$user->setClient($client);
                                $user->setEnabled(true);
                                $user->setEntreprise($entrepriseObject);

                                if (isset($meta)) {
                                    $user->setMeta($meta);
                                }
                                $user->setEntrepriseCourante($entrepriseCourante->getCode());
                                $user->addRole('ROLE_USER');
                                $user->setEtat('client');

                                $em->persist($user);
                                $em->flush();
                            }
                        }
                    }
                    if (count($metaClientForm->getErrors(1)) === 0) {
                        return $this->redirect($this->generateUrl($clientEditRoute, [
                            'codeClient' => $client->getCode(),
                            'step' => $steps[$step]['nextStep'],
                            'mode' => $mode,
                        ]));
                    }
                } else if ($credentialsForm->isValid() && ($client instanceof Client) && $request->request->get('client_credentials') !== null) {
                    if (trim($formDataCredentials->getPassword()) !== "") {
                        if ($userObj instanceof User) {
                            // notez que $formData est dans ce cas de type User et possède donc la méthode getZone()
                            $encoder = $this->container->get('security.password_encoder');
                            $password = $encoder->encodePassword($userObj, $formDataCredentials->getPassword());
                            $userObj->setPassword($password);
                            $em->persist($userObj);
                            $em->flush();
                        }
                    } else {
                        $credentialError = new FormError("Vous devez saisir un mot de passe.");
                        $credentialsForm->addError($credentialError);
                    }

                    if (count($credentialsForm->getErrors(1)) === 0) {
                        return $this->redirect($this->generateUrl($clientEditRoute, [
                            'codeClient' => $client->getCode(),
                            'step' => $steps[$step]['nextStep'],
                            'mode' => $mode,
                        ]));
                    }
                }

                $paramViews['metaClientForm'] = $metaClientForm->createView();
                $paramViews['credentialsForm'] = $credentialsForm->createView();
                $paramViews['metaClients'] = $metaClients;
                break;
            case 'general':
                $optionsClient = new SetupClientOptionsType();
                $optionsClient->setCodeEntreprise($codeEntreprise);
                $optionsClient->setAmbient($multiSiteService->hasTemperature('ambient'));
                $optionsClient->setPositiveCold($multiSiteService->hasTemperature('positiveCold'));
                $optionsClient->setNegativeCold($multiSiteService->hasTemperature('negativeCold'));
                $optionsForm = $this->createForm($optionsClient, $userObj);

                if ($userObj instanceof User) {
                    $optionsForm->setData($userObj);
                }

                $optionsForm->handleRequest($request);
                $formDataOptions = $optionsForm->getData();
                if ($optionsForm->isSubmitted() && ($client instanceof Client) && $optionsForm->isValid() && $request->request->get('client_options') !== null) {
                    $userObj = null;
                    if ($client->getMeta() !== null) {
                        $userObj = $UserRepository->findOneBy(array('meta' => $client->getMeta()->getCode()));
                    }

                    if (array_key_exists('zoneSec', $formDataOptions) && $formDataOptions['zoneSec'] instanceof Zone) {
                        $zoneSec = $ZoneRepository->findOneByCode($formDataOptions['zoneSec']->getCode());
                        $userObj->setZoneSec($zoneSec);
                    }

                    if (array_key_exists('zoneFrais', $formDataOptions) && $formDataOptions['zoneFrais'] instanceof Zone) {
                        $zoneFrais = $ZoneRepository->findOneByCode($formDataOptions['zoneFrais']->getCode());
                        $userObj->setZoneFrais($zoneFrais);
                    }
                    if (array_key_exists('zoneSurgele', $formDataOptions) && $formDataOptions['zoneSurgele'] instanceof Zone) {
                        $zoneSurgele = $ZoneRepository->findOneByCode($formDataOptions['zoneSurgele']->getCode());
                        $userObj->setZoneSurgele($zoneSurgele);
                    }

                    $userObj->setCgvCpvUpdatedAt(new \DateTime('now'));

                    if ($formDataOptions->getCgvCpv() !== null) {

                        // cette branche n'est exécutée que si le type de contrat est choisi par l'admin

                        $userObj->setCgvCpv($formDataOptions->getCgvCpv());
                        if ($formDataOptions->isAlreadySigned()) {
                            $userObj->setCgvCpvSignedAt(new \DateTime('now'));
                            $userObj->setAlreadySigned(true);
                        } else {
                            $userObj->setCgvCpvSignedAt(null);
                            $userObj->setAlreadySigned(false);
                        }
                        $userObj->setAlreadySigned($formDataOptions->isAlreadySigned());
                    } else {
                        $userObj->setCgvCpv($multiSiteService->getValue("type-contrat"));      // cette branche n'est exécutée que si le type de contrat est défini dans la config
                        $userObj->setCgvCpvSignedAt(null);
                        $userObj->setAlreadySigned(false);
                    }

                    if ($formDataOptions->getMontantFranco() == null) {
                        $userObj->setMontantFranco($multiSiteService->getValue("franco"));
                    } else {
                        $userObj->setMontantFranco($formDataOptions->getMontantFranco());
                    }
                    $userObj->setFlagFranco($formDataOptions->isFlagFranco());

                    $em->persist($userObj);
                    $em->flush();

                    return $this->redirect($this->generateUrl($clientEditRoute, [
                        'codeClient' => $client->getCode(),
                        'step' => $steps[$step]['nextStep'],
                        'mode' => $mode,
                    ]));
                }

                $paramViews['optionsForm'] = $optionsForm->createView();
                $paramViews['franco'] = $multiSiteService->hasFeature('franco');
                $paramViews['zoneSec'] = $multiSiteService->hasTemperature('ambient');
                $paramViews['zoneFrais'] = $multiSiteService->hasTemperature('positiveCold');
                $paramViews['zoneSurgele'] = $multiSiteService->hasTemperature('negativeCold');
                $paramViews['type_contrat'] = $multiSiteService->hasFeature('type-contrat');
                break;
            case 'enseigne-assortiment':
                $enseigneAssortimentClient = new AddClientEnseigneAndAssortimentType();
                $enseigneAssortimentForm = $this->createForm($enseigneAssortimentClient);

                $enseigneAssortimentForm->handleRequest($request);
                if ($enseigneAssortimentForm->isSubmitted() && $enseigneAssortimentForm->isValid() && ($client instanceof Client)) {
                    $data = $enseigneAssortimentForm->getData();

                    // MAJ de l'enseigne du client : Sictoz
                    $client->setEnseigne($data["enseigne"]);
                    $em->persist($client);

                    $assortimentClient = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(array("client" => $client->getCode(), "valeur" => $data["assortiment"]->getValeur()));
                    $assortimentClientCourant = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(array("client" => $client->getCode(), "assortimentCourant" => true));
                    if (!$assortimentClient) {
                        $assortimentClient = new AssortimentClient();
                        $assortimentClient->setClient($client);
                        $assortimentClient->setValeur($data["assortiment"]->getValeur());
                        $assortimentClient->setAs400assortiment(1);
                        $assortimentClient->setAssortimentCourant(true);
                        $em->persist($assortimentClient);

                        $assortimentClientCourant->setAssortimentCourant(false);
                        $em->persist($assortimentClientCourant);
                    } elseif ($assortimentClient->getId() !== $assortimentClientCourant->getId()) {
                        $assortimentClient->setAssortimentCourant(true);
                        $em->persist($assortimentClient);

                        $assortimentClientCourant->setAssortimentCourant(false);
                        $em->persist($assortimentClientCourant);
                    }

                    $em->flush();

                    return $this->redirect($this->generateUrl($clientEditRoute, [
                        'codeClient' => $client->getCode(),
                        'step' => $steps[$step]['nextStep'],
                        'mode' => $mode,
                    ]));
                }

                $paramViews['enseigneAssortimentForm'] = $enseigneAssortimentForm->createView();
                break;
            case 'geocoding':
                $geolocationClient = new GeolocationType();
                $geolocationForm = $this->createForm($geolocationClient, $client);

                $geolocationForm->handleRequest($request);
                if ($geolocationForm->isSubmitted() && $geolocationForm->isValid() && ($client instanceof Client) && $request->request->get('sogedial_userbundle_geolocation') !== null) {
                    $em->persist($client);
                    $em->flush();
                    return $this->redirect($this->generateUrl($clientEditRoute, [
                        'codeClient' => $client->getCode(),
                        'step' => $steps[$step]['nextStep'],
                        'mode' => $mode,
                    ]));
                }

                $paramViews['geolocationForm'] = $geolocationForm->createView();
                break;
            default:
                return $this->redirect($this->generateUrl('SogedialSite_admin_list_users'));
                break;
        }

        // Create breadcrumb nodes
        $splitCodeClient = preg_split('/-/', $codeClient)[1];
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        $breadcrumbs->addRouteItem('Mes clients', 'sogedial_integration_admin_mesclients');
        //$breadcrumbs->addRouteItem($splitCodeClient, 'sogedial_integration_admin_client', ['id' => $codeClient]); TODO : To be reactivated later
        $breadcrumbs->addItem($splitCodeClient);
        $breadcrumbs->addItem('Edition client');

        // Fill general view parameters.
        $paramViews['mode'] = $mode;
        $paramViews['currentStep'] = $steps[$step];
        $paramViews['steps'] = $steps;
        $paramViews['clientEditRoute'] = $clientEditRoute;
        $paramViews['client'] = $client;
        $paramViews['codeClient'] = $codeClient;
        $paramViews['username'] = isset($userObj) ? $userObj->getUsername() : $splitCodeClient;
        $paramViews['nbrAllProduit'] = $ProduitRepository->getAllProduitNumber('sec');
        $paramViews['nbrProduitWithoutSource'] = $ProduitRepository->getProduitWithoutSourceNumber('sec');
        $paramViews['nbrOrder'] = $CommandeRepository->getOrderNumber();
        $paramViews['commercialInfo'] = $this->commercialInfo();
        return $this->render('SogedialIntegrationBundle:Admin/ModificationClient:ajouter-client-' . $step . '.html.twig', $paramViews);
    }

    public function lockedClientsAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:locked-clients.html.twig', $viewParams);
    }

    /**
     * @return JsonResponse
     */
    public function getProduitWithoutSourceJsonAction()
    {
        $produits = $this
            ->getDoctrine()
            ->getRepository('SogedialSiteBundle:Produit')
            ->getCatalogueProduitWithoutSource('sec');

        return new JsonResponse(['data' => $produits]);
    }

    /**
     * @return JsonResponse
     */
    public function getAllProduitJsonAction()
    {
        $produits = $this
            ->getDoctrine()
            ->getRepository('SogedialSiteBundle:Produit')
            ->getCatalogueAllProduit('sec');

        return new JsonResponse(['data' => $produits]);
    }

    public function getProduitWithoutSourceAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        //return $this->render('SogedialSiteBundle:Admin:list-produit-without-source.html.twig', $viewParams);
        return $this->render('SogedialIntegrationBundle:Admin:catalogue.cc.html.twig', $viewParams);
    }

    public function getAllProduitAction()
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:list-all-produit.html.twig', $viewParams);
    }

    /**
     * @ParamConverter("produit", class="SogedialSiteBundle:Produit")
     * @return type
     */
    public function photoAssociationAction(Request $request, Produit $product)
    {
        $em = $this->getDoctrine()->getManager();
        $formImage = $this->get('form.factory')->createNamedBuilder('formImage', new UploadPhotoType(), $product)->getForm();

        if ($request->getMethod() == 'POST') {
            if ($request->request->has('formImage')) {
                $formImage->handleRequest($request);
                $file = $formImage['attachment']->getData();
                if ($formImage->isValid() && $file != null) {
                    $dir = $this->get('kernel')->getRootDir() . '/../web/images/product/original/';
                    $file->move($dir, $file->getClientOriginalName());
                    $photo = new Photo();
                    $photo->setCover(0);
                    $photo->setDisplay(0);
                    $libelle = strtr(strtolower(substr($file->getClientOriginalName(), 0, -4)), 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
                    $photo->setLibelle($libelle);
                    $photo->setSource($file->getClientOriginalName());
                    $product->addPhoto($photo);
                    $em->persist($product);
                    $em->flush();
                }
            }

        }

        $paramsView = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber(),
            'formImage' => $formImage->createView(),
            'commercialInfo' => $this->commercialInfo(),
            'product' => $product
        );

        return $this->render('SogedialIntegrationBundle:Admin:update.photo.html.twig', $paramsView);


    }

    /**
     * @param $codeClient
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function blockClientAccessAction($codeClient)
    {
        $em = $this->getDoctrine()->getManager();

        $client = $em->getRepository('SogedialSiteBundle:Client')
            ->findOneBy(array('code' => $codeClient));

        if ($client instanceof Client) {
            $client->setEActif(0);
            $em->persist($client);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sogedial_integration_admin_mesclients'));
    }

    /**
     * @param $codeProspect
     */
    public function blockProspectAccessAction($codeProspect)
    {
        $em = $this->getDoctrine()->getManager();

        $prospect = $em->getRepository('SogedialSiteBundle:Client')->find($codeProspect);

        if ($prospect instanceof Client) {
            $prospect->setEActif(0);
            $em->persist($prospect);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sogedial_integration_admin_mesprospects'));
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function validCommandAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $orderStatut = $em->getRepository('SogedialSiteBundle:OrderStatus')
            ->findOneByKey('STATUS_APPROVED');

        $orderOrderStatus = $em->getRepository('SogedialSiteBundle:OrderOrderStatus')
            ->findOneByOrder($id);

        if ($orderOrderStatus instanceof OrderOrderStatus) {
            $orderOrderStatus->setOrderStatus($orderStatut);
            $em->persist($orderOrderStatus);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sogedial_integration_admin_commandes_clients'));
    }

    public function blockClientAccess2Action($codeClient)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        $user = $em->getRepository('SogedialUserBundle:User')
            ->findOneBy(array('client' => $codeClient));

        if ($user instanceof User) {
            $user->setEnabled(0);
            $em->persist($user);
            $em->flush();
        }

        $viewParams = array(
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'commercialInfo' => $this->commercialInfo(),
            'mesClients' => $em->getRepository('SogedialSiteBundle:Client')->getListClients2('ALL'),
            '_societe_cc' => $code_entreprise
        );
        return $this->render('SogedialIntegrationBundle:Admin:mesclients.html.twig', $viewParams);
    }

    /**
     * @param $codeClient
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unblockClientAccessAction($codeClient)
    {
        $em = $this->getDoctrine()->getManager();

        $client = $em->getRepository('SogedialSiteBundle:Client')
            ->findOneBy(array('code' => $codeClient));

        if ($client instanceof Client) {
            $client->setEActif(1);
            $em->persist($client);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sogedial_integration_admin_mesclients'));

    }

    /**
     * @param $codeProspect
     */
    public function unblockProspectAccessAction($codeProspect)
    {
        $em = $this->getDoctrine()->getManager();

        $prospect = $em->getRepository('SogedialSiteBundle:Client')->find($codeProspect);

        if ($prospect instanceof Client) {
            $prospect->setEActif(1);
            $em->persist($prospect);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('sogedial_integration_admin_mesprospects'));

    }

    /**
     * @param $codeClient
     * @return Response
     */
    public function unblockClientAccess2Action($codeClient)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }
        $client = $em->getRepository('SogedialSiteBundle:Client')
            ->findOneBy(array('code' => $codeClient));

        if ($client instanceof Client) {
            $client->setEActif(1);
            $em->persist($client);
            $em->flush();
        }

        $viewParams = array(
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'commercialInfo' => $this->commercialInfo(),
            'mesClients' => $em->getRepository('SogedialSiteBundle:Client')->getListClients2('ALL'),
            '_societe_cc' => $code_entreprise
        );
        return $this->render('SogedialIntegrationBundle:Admin:mesclients.html.twig', $viewParams);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function trackingUsersAction(Request $request)
    {
        $viewParams = array(
            'nbrAllProduit' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getAllProduitNumber('sec'),
            'nbrProduitWithoutSource' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Produit')->getProduitWithoutSourceNumber('sec'),
            'numberOfClients' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClients(),
            'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess(),
            'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess(),
            'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients(),
            'nbrOrder' => $this->getDoctrine()->getRepository('SogedialSiteBundle:Commande')->getOrderNumber()
        );

        return $this->render('SogedialSiteBundle:Admin:tracked-users.html.twig', $viewParams);
    }

    /**
     * @ParamConverter("commande", class="SogedialSiteBundle:Commande", options={"mapping": {"id": "id"}})
     * @param Request $request
     */
    public function recapOrderExcelAction(Request $request, Commande $commande)
    {
        return $this->get('sogedial.export')->toExcelRecapExport($commande);
    }

    public function messagesListAction()
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $this->get('sogedial.multisite')->initSessionUserAdmin($this->get('security.token_storage')->getToken());

         $messagesClients = $em->getRepository('SogedialSiteBundle:MessageClient')->getMessagesClientsByCodeEntreprise($code_entreprise);

        // // Create breadcrumb nodes
        // $breadcrumbs = $this->get("white_october_breadcrumbs");
        // $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        // $breadcrumbs->addItem('Mes clients');

        // $query = false;
        // if ($request->query !== NULL && $request->query->get('clients')) {
        //     $query = $request->query->get('clients');
        // }

        // $clients = $em->getRepository('SogedialSiteBundle:Client')->getListClients2($status, $page, $code_entreprise, $query);

        $viewParams = array(
            // 'numberOfClientsWithAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithAccess($code_entreprise),
            // 'numberOfClientsWithoutAccess' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->getNumberOfClientsWithoutAccess($code_entreprise),
            // 'numberOfClientsLocked' => $this->getDoctrine()->getRepository('SogedialUserBundle:User')->numberOflockedClients($code_entreprise),
            'commercialInfo' => $this->commercialInfo(),
            // 'mesClients' => $clients,
            "_societe_cc" => $code_entreprise,
            "mode" => "showList",
            "mesClientsActifs" => null,
            "messagesClients" => $messagesClients,
            "message" => null
        );

        return $this->render('SogedialIntegrationBundle:Admin:messagesClient.html.twig',$viewParams);
    }

    public function messagesNewAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $this->get('sogedial.multisite')->initSessionUserAdmin($this->get('security.token_storage')->getToken());

        // // Create breadcrumb nodes
        // $breadcrumbs = $this->get("white_october_breadcrumbs");
        // $breadcrumbs->addRouteItem('Dashboard', 'sogedial_integration_admin_dashbord');
        // $breadcrumbs->addItem('Mes clients');

        $query = false;
        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $clients = $em->getRepository('SogedialSiteBundle:Client')->getActiveClientsByCodeEntreprise($code_entreprise);
        $viewParams = array(
            'commercialInfo' => $this->commercialInfo(),
            'mesClientsActifs' => $clients,
            "_societe_cc" => $code_entreprise,
            "mode" => "new",
            "message" => null,
            "messagesClients" => null,
        );

        return $this->render('SogedialIntegrationBundle:Admin:messagesClient.html.twig',$viewParams);
    }

    public function messagesEditAction(Request $request, $messageId)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        if ($this->getUser()->getEntreprise() !== NULL) {
            $code_entreprise = $this->getUser()->getEntreprise()->getCode();
        }

        $this->get('sogedial.multisite')->initSessionUserAdmin($this->get('security.token_storage')->getToken());

        $query = false;
        if ($request->query !== NULL && $request->query->get('clients')) {
            $query = $request->query->get('clients');
        }

        $clients = $em->getRepository('SogedialSiteBundle:Client')->getActiveClientsByCodeEntreprise($code_entreprise);
        $message = $em->getRepository('SogedialSiteBundle:MessageClient')->findOneById($messageId);
        $viewParams = array(
            'commercialInfo' => $this->commercialInfo(),
            'mesClientsActifs' => $clients,
            "_societe_cc" => $code_entreprise,
            "mode" => "edit",
            "message" => $message,
            "messagesClients" => null
        );

        return $this->render('SogedialIntegrationBundle:Admin:messagesClient.html.twig',$viewParams);
    }

    public function createOrEditMessageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $code_entreprise = false;
        $entreprise = $this->getUser()->getEntreprise();
        if ($entreprise !== NULL) {
            $code_entreprise = $entreprise->getCode();
        }

        $this->get('sogedial.multisite')->initSessionUserAdmin($this->get('security.token_storage')->getToken());

        $messageId = $request->request->get('messageId');
        $messageLibelle = $request->request->get('messageLibelle');
        $messageText = $request->request->get('messageContent');
        $messageDateDebut = $request->request->get('dateDebut');
        $messageDateFin = $request->request->get('dateFin');
        // $message['e_actif'] = $request->request->get('e_actif');
        // $message['listeDestinataires'] = $request->request->get('listeDestinataires');
        $clients = $em->getRepository('SogedialSiteBundle:Client')->getActiveClientsByCodeEntreprise($code_entreprise);


        if ($messageId != null){
            $em->getRepository('SogedialSiteBundle:MessageClient')->editMessageById($messageId, $messageLibelle, $messageText, $messageDateDebut, $messageDateFin);
        } else {
            /*$em->getRepository('SogedialSiteBundle:MessageClient')->createMessageClient($messageCode, $messageLibelle, $messageText, $messageDateDebut, $messageDateFin, $clients);*/
            $message = new MessageClient();
            $message->setLibelle($messageLibelle);
            $message->setText($messageText);
            $message->setDateFinValidite(new \DateTime($messageDateFin));
            $message->setDateDebutValidite(new \DateTime($messageDateDebut));
            $message->setEntreprise($entreprise);
            $em->persist($message);
            $em->flush();

            foreach ( $clients as $client ) {
                $clientObject = $em->getRepository('SogedialSiteBundle:Client')->findOneByCode(Array( 'code' => $client['code']));
                if ($clientObject instanceof Client){
                    $clientObject->addMessageClient($message);
                }
                $em->persist($clientObject);
                $em->persist($message);
            }
            $em->flush();
        }
        return new JsonResponse(['data' => "ok"]);
    }
}