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

class LeftSidebarController extends Controller
{
    public function indexAction($commercialInfo) {
        $em = $this->getDoctrine()->getManager();
        $commandesRepository = $em->getRepository(
            'SogedialSiteBundle:Commande'
        );

        $user = $this->getUser();
        $societe = $user->getEntreprise()->getCode();

        $paramViews = array(
            'commercialInfo' => $commercialInfo,
            'pendingOrdersCount' => $commandesRepository->countOrdersByMOQStatus("VALID", $societe),
            'pendingMoqCount' => $commandesRepository->countCommandLineByMOQStatus("VALID", $societe),
            'preCommandeAdmin' => $user->getPreCommande() !== NULL,
            'entreprise' => $user->getEntreprise()->getRaisonSociale()
        );

        return $this->render("SogedialIntegrationBundle:Admin:aside.html.twig", $paramViews);
    }

    public function assortimentLandingAction(Request $request){
        // Pour fixer le problème de redirection avec le referer
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $em = $this->getDoctrine()->getManager();
        $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());

        $paramViews = array(
            'listAssortiments' => $this->get('sogedial_integration.catalogue')->getListAssortimentsByCodeClient($clientInfo['code'])
        );

        return $this->render('SogedialIntegrationBundle:ChangeCompany:change-assortiment-index.html.twig', $paramViews);
        
    }

    public function societeLandingAction(Request $request) {

        // Pour fixer le problème de redirection avec le referer
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('sogedial_integration_admin_dashbord'));
        }

        $em = $this->getDoctrine()->getManager();
        $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());

        $paramViews = array(
            'listSociete' => $this->get('sogedial_integration.catalogue')->getListSocieteByCodeRegionAndCodeClient($clientInfo['code'])
        );

        return $this->render('SogedialIntegrationBundle:ChangeCompany:change-company-index.html.twig', $paramViews);
    }

     /**
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function societeLoadAction(Request $request, $societe)
    {
        $em =  $this->getDoctrine()->getManager();
        $societeObject = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($societe);
        $societeLabelle = $societeObject->getNomEnvironnement();

        $url = $this->generateUrl('sogedial_integration_dashbord', array('societe' => $societeLabelle));
        $masterEnterprise = ucfirst(strtolower($societe));
        $multiSiteService = $this->get('sogedial.multisite');
        $multiSiteService->setMasterEnterpriseTwig($masterEnterprise);

        $myClients = $this->getUser()->getMeta()->getClients();
        $preCommandeMode = $this->getUser()->getPreCommande() !== NULL;
        foreach ($myClients as $key => $client) {
            if($client->getEntreprise()->getCode() ===  $societe){
                if($preCommandeMode) {
                    $url = $this->generateUrl('sogedial_integration_catalogue', ["societe" => $societeLabelle]);
                }

                if($multiSiteService->getRegion() === '3'){
                    $valeurAssortiment = '777';
                } else {
                    $valeurAssortiment = $this->get('sogedial.multisite')->getAssortimentValeur($client);
                }
                if($this->get('sogedial.multisite')->hasFeature('tarifs-tarification')){
                    $this->get('sogedial.userinfo')->switchClient($client->getCode(), $client->getEnseigne()->getCode(), $client->getTarification()->getCode(), $valeurAssortiment, $societe);
                } else {
                    $this->get('sogedial.userinfo')->switchClient($client->getCode(), $client->getEnseigne()->getCode(), null, $valeurAssortiment, $societe);
                }
                $userObject = $this->getUser();
                $userObject->setEntrepriseCourante($societe);
                $em->persist($userObject);
                $em->persist($client);
                $em->flush();
                break;
            }
        }

        return $this->redirect($url);
    }

    public function assortimentLoadAction(Request $request, $valeur)
    {
        $em =  $this->getDoctrine()->getManager();

        $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(
            array(
                "entreprise" => $this->getUser()->getEntrepriseCourante(), 
                "meta" => $this->getUser()->getMeta()->getCode()
            )
        );
        /*
        $url = $this->generateUrl('sogedial_integration_catalogue', ["societe" => $this->getUser()->getEntrepriseCourante()]);

        $assortimentsClientCourant = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(
            array(
                "client" => $client->getCode(),
                "assortimentCourant" => true 
            )
        );
        $assortimentsClientChoose = $em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy(
            array(
                "client" => $client->getCode(),
                "valeur" => $valeur 
            )
        );

        //nothing change
        if($assortimentsClientChoose->getId() === $assortimentsClientCourant->getId()){
            return $this->redirect($url);
        }

        $assortimentsClientChoose->setAssortimentCourant(true);
        $assortimentsClientCourant->setAssortimentCourant(false);

        $em->persist($assortimentsClientCourant);
        $em->persist($assortimentsClientChoose);
        $em->flush();
        */

        $url = $this->generateUrl('sogedial_integration_catalogue', ["societe" => $this->getUser()->getEntrepriseCourante()]);        
        if(!($this->get("sogedial.assortimentclient")->chooseAssortimentClient($client->getCode(), $valeur))){
            return $this->redirect($url);            
        }
        
        if($this->get('sogedial.multisite')->hasFeature('tarifs-tarification')){
            $this->get('sogedial.userinfo')->switchClient($client->getCode(), $client->getEnseigne()->getCode(), $client->getTarification()->getCode(), $valeur, $this->getUser()->getEntrepriseCourante());
        } else {
            $this->get('sogedial.userinfo')->switchClient($client->getCode(), $client->getEnseigne()->getCode(), null, $valeur, $this->getUser()->getEntrepriseCourante());
        }
        
        return $this->redirect($url);
    }
}
