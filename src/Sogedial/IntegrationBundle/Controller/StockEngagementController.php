<?php

namespace Sogedial\IntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sogedial\SiteBundle\Entity\Promotion;
use Sogedial\SiteBundle\Entity\MoreStockRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Produit;


class StockEngagementController extends Controller
{
    /**
     * @param $id code_promotion from promotion table
     * @return Response
     */
    public function indexAction($id, $societe)
    {
        $em = $this->getDoctrine()->getManager();
        $promotion = $em->getRepository('SogedialSiteBundle:Promotion')->find($id);
        $currentUser = $this->getUser();
        $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());

        $paramViews = array(
            'promotion' => $promotion,
            'code_produit' => $promotion->getProduit()->getCode(),
            'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
            'clientInfo' => $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId()),
            'societe' => $entrepriseObj->getNomEnvironnement()
        );

        return $this->render('SogedialIntegrationBundle:StockEngagement:index.html.twig', $paramViews);
    }

    /**
     * Update stock_engagement_demande and send mail to CC
     * @param $id code_promotion from promotion table
     * @return Response
     */
    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $promotion = $em->getRepository('SogedialSiteBundle:Promotion')->findOneBy(array("code" => $id));
        $client = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(
            array(
                "meta" => $this->getUser()->getMeta()->getCode(),
                "entreprise" => $this->getUser()->getEntrepriseCourante()
            )
        );
        $product = $em->getRepository('SogedialSiteBundle:Produit')->getProduitByCode($promotion->getProduit()->getCode());
        $stockEngagementDemande = $request->request->get('stock_engagement_demande');

        $promotion->setStockEngagementDemande($stockEngagementDemande);
        $em->persist($promotion);

        $moreStockRequest = $em->getRepository('SogedialSiteBundle:MoreStockRequest')->findOneBy(
            array(
                "promotion" => $id,
                "client" => $client
            )
        );
        if(!$moreStockRequest){
            $moreStockRequest = new MoreStockRequest();
            $moreStockRequest->setClient($client);
            $moreStockRequest->setPromotion($promotion);
        }
        
        $moreStockRequest->setQuantityStockRequested($stockEngagementDemande);
        $em->persist($moreStockRequest);

        $em->flush();
        
        return new JsonResponse(array(
            'update' => true,
            "promotion" => $promotion->getCode()
        ));
    }
}