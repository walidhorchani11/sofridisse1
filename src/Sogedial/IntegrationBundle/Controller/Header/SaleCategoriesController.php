<?php

namespace Sogedial\IntegrationBundle\Controller\Header;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SaleCategoriesController extends Controller
{
    public function indexAction($entrepriseName) {
        $em = $this->getDoctrine()->getManager();
        $multiSiteService = $this->get('sogedial.multisite');
        $societe = $multiSiteService->getSociete();
        if (is_null($entrepriseName)) {
            // Gather the required attributes and services.
            
            // Get the current user's code societe.
            $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($societe);
            $entrepriseName = $entrepriseObj->getNomEnvironnement();
        }

        // Fetch categories' products count.
        $families = $this->get('sogedial_integration.catalogue')->getSidebarElement($this->getUser());
        $username = $this->getUser()->getUsername();

        // Fill all categories' info.
        $categories = [
            'promotions' => [
                'counter' => $families["promotions"]["counter"],
                'link' => $this->generateUrl('sogedial_integration_catalogue', [
                    'societe' => $entrepriseName,
                    'kind' => 'promotion',
                ]),
                'pictoPath' => 'images/sale.svg',
                'title' => 'Promotions',
            ],
            'nouveautes' => [
                'counter' => $families["nouveautes"]["counter"],
                'link' => $this->generateUrl('sogedial_integration_catalogue', [
                    'societe' => $entrepriseName,
                    'kind' => 'new',
                ]),
                'pictoPath' => 'images/new.svg',
                'title' => 'Nouveautés',
            ],
            'selections' => [
                'counter' => 0,
                'link' => null,
                'pictoPath' => 'images/star.png',
                'title' => 'Sélections',
                'selectionDropdown' => true
            ],
        ];
        $listAssortiments = $this->get('sogedial_integration.catalogue')->getListAssortimentsByCodeClient(sprintf('%s-%s',$societe,$username));

        $paramViews = [
            'categories' => $categories,
            'listAssortiments' => [
                'list' => $listAssortiments,
                'counter' => count($listAssortiments) - 1
            ]
        ];
        return $this->render("SogedialIntegrationBundle:Layout:Header/sale-categories.html.twig", $paramViews);
    }
}
