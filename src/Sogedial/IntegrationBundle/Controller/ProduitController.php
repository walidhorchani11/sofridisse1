<?php

namespace Sogedial\IntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sogedial\IntegrationBundle\Form\Type\ResearchType;
use Hackzilla\BarcodeBundle\Utility\Barcode;
use Doctrine\ORM\NoResultException;

class ProduitController extends Controller
{
    public function ficheProduitAction(Request $request, $societe, $code)
    {
        $em = $this->getDoctrine()->getManager();
        $iterator = $this->get('sogedial.product')->getSingleProduct($code);
        $catalogueService = $this->get('sogedial_integration.catalogue');
        $userRepository = $em->getRepository('SogedialUserBundle:User');
        $products = $catalogueService->refactorAllProducts($iterator);
        $form = $this->createForm(new ResearchType());
        $userId = $this->getUser()->getId();
        $container = $this->container;
        $search = "";

        $form->handleRequest($request);
        $product = current($products);
        $OrderTotalAmount = $this->get('sogedial_integration.catalogue')->calculateOrderTotalAmount();
        $productHasMoq = $this->getDoctrine()->getRepository('SogedialSiteBundle:ProduitRegle')->doesProductHaveMoq($product['code']);

        $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId(), $this->getUser()->getEntrepriseCourante());
        $qMinArray = $em->getRepository('SogedialSiteBundle:ClientProduitMOQ')
                            ->getQMinFromCodeClientAndCodeProduit($clientInfo['code'], $product["code"]);
        if($qMinArray){
            $qMinArray = $qMinArray["quantiteMinimale"];
        }

        if($request->query->get('produits')){
            $search = $request->query->get('produits');
        }

        // Prepare parameters for links/routing in the breadcrumbs
        $productNomSecteur = $product['secteur'];
        $productCodeSecteur = $product['CodeSecteur'];
        $productNomRayon = $product['rayon'];
        $productCodeRayon = (array_key_exists("codeRayon", $product)) ? $product['codeRayon']: NULL;
        $productNomFamille = $product['sf_fr'];
        $productCodeFamille = $product['sf'];
        $productName = $product['denominationProduitBase'];

        $catalogueBaseRoute = 'sogedial_integration_catalogue';
        $secteurParameters = [
          'societe' => $societe,
          'codeSecteur' => $productCodeSecteur
        ];
        $rayonParameters = [
          'societe' => $societe,
          'codeSecteur' => $productCodeSecteur,
          'codeRayon' => $productCodeRayon
        ];
        $familleParameters = [
          'societe' => $societe,
          'codeSecteur' => $productCodeSecteur,
          'codeRayon' => $productCodeRayon,
          'codeFamille' => $productCodeFamille
        ];

        // Create breadcrumb nodes
        $breadcrumbs = $this->get("white_october_breadcrumbs");

        if ($this->getUser()->getPreCommande() == NULL){
            $breadcrumbs->addRouteItem("Dashboard", "sogedial_integration_dashbord", [
                'societe' => $societe,
            ]);
        }
        $breadcrumbs->addRouteItem('Catalogue produits', $catalogueBaseRoute, [
            'societe' => $societe]);
        $breadcrumbs->addRouteItem($productNomSecteur, $catalogueBaseRoute, $secteurParameters);
        $breadcrumbs->addRouteItem($productNomRayon, $catalogueBaseRoute, $rayonParameters);
        $breadcrumbs->addRouteItem($productNomFamille, $catalogueBaseRoute, $familleParameters);
        $breadcrumbs->addItem($productName);

        $viewParams = array(
            'preCommandeMode' => $this->getUser()->getPrecommande() !== NULL,
            'commercialInfo' => $userRepository->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
            'clientInfo' => $userRepository->getClientInformation($userId),
            'product' => $product,
            'result' => $catalogueService->getCommande(),
            'baseUrlProduit' => $container->getParameter('baseUrlProduit'),
            'form' => $form->createView(),
            'request' => $request,
            'search' => $search,
            'routeName' => "sogedial_integration_fiche_produit",       // required in case of forwarding
            'totalAmount' => $OrderTotalAmount['totalAmount'],
            'societe' => $societe,
            'productHasMoq' => $productHasMoq,
            "moq_client" => $qMinArray,
            "client" => $clientInfo['code'],
            'currentPage' => 'produit',
        );

        return $this->render(
            'SogedialIntegrationBundle:Produit:fiche-produit.html.twig',
            $viewParams
        );
    }
}