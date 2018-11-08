<?php

namespace Sogedial\IntegrationBundle\Controller;

use Sogedial\IntegrationBundle\Form\Type\ResearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashbordController extends Controller
{
    public function indexAction(Request $request, $societe, $page)
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        $entityClient = $em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $currentUser->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));

        if ($currentUser->getPreCommande() !== NULL) {
            $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());
            return $this->redirect($this->generateUrl('sogedial_integration_catalogue', array("societe" => $entrepriseObj->getNomEnvironnement())));

        }

        $multiplyByPcb = !($this->get('sogedial.multisite')->hasFeature('vente-par-unite'));
        //Patch temporaire pour eviter info de session vide ou mauvaise
        $societeCC = false;
        if ($request->query->get('_societe_cc') && $request->query->get('_societe_cc') != '') {
            $societeCC = $request->query->get('_societe_cc');
        }

        $this->get('sogedial.multisite')->initSessionUser($this->get('security.token_storage')->getToken(), $societeCC);

        $clientInfo = $em->getRepository('SogedialUserBundle:User')->getClientInformation($this->getUser()->getId());

        $listProductsResearchByRayon = array();
        $listResearchRayon = array();
        $search = ($request->query->get('produits')) ? $request->query->get('produits') : '';

        $formType = new ResearchType();
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        $messageClients =  $entityClient->getMessageClients()->filter(function($messageClient) {
            $today = new \DateTime('now');
            if ($messageClient->getDateDebutValidite() <= $today && $messageClient->getDateFinValidite() >= $today){
                return true;
            }
        });

        $ps = $this->get('sogedial.product');
        $promonewsRaw = $ps->getNewProductsAndPromosForDashboard();
        // enrich with additional data
        $promonews = $this->get('sogedial_integration.catalogue')->refactorAllProducts($promonewsRaw);

        $command = $this->get('sogedial_integration.catalogue')->getCommande();
        $OrderTotalAmount = $this->get('sogedial_integration.catalogue')->calculateOrderTotalAmount();
        if ($currentUser->getZoneSec() && $this->get('sogedial.multisite')->hasTemperature("ambient")) {
            $command["order"]["nextDelivery"]["ambient"] = $currentUser->getZoneSec()->getDeliveryNextDate();              // la répétition est volontaire, en préparation des dates de livraison par température
        }
        if ($currentUser->getZoneFrais() && $this->get('sogedial.multisite')->hasTemperature("positiveCold")) {
            $command["order"]["nextDelivery"]["positiveCold"] = $currentUser->getZoneFrais()->getDeliveryNextDate();
        }
        if ($currentUser->getZoneSurgele() && $this->get('sogedial.multisite')->hasTemperature("negativeCold")) {
            $command["order"]["nextDelivery"]["negativeCold"] = $currentUser->getZoneSurgele()->getDeliveryNextDate();
        }
        //TODO : Deleted for the while @sictozs
        /*else {
            $date = new \DateTime();
            $date->add(new \DateInterval("P1D"));

            if ($this->get('sogedial.multisite')->hasTemperature("ambient")) {
                $command["order"]["nextDelivery"]["ambient"] = $date;
            }
            if ($this->get('sogedial.multisite')->hasTemperature("positiveCold")) {
                $command["order"]["nextDelivery"]["positiveCold"] = $date;
            }
            if ($this->get('sogedial.multisite')->hasTemperature("negativeCold")) {
                $command["order"]["nextDelivery"]["negativeCold"] = $date;
            }
        }*/

        // If the client is a prospect, retrieve his expiration date.
        $isProspect = $entityClient->isProspect();

        if ($isProspect) {
            $prospectCodeZone = null;
            if (null !== $currentUser->getZoneSec()) {
                $prospectCodeZone = $this->formatListZone($currentUser->getZoneSec());
            }

            if (null !== $currentUser->getZoneFrais()) {
                $prospectCodeZone = $this->formatListZone($currentUser->getZoneFrais());
            }

            if (null !== $currentUser->getZoneSurgele()) {
                $prospectCodeZone = $this->formatListZone($currentUser->getZoneSurgele());
            }

            $prospectOptions = [
                'comment' => (null !== $entityClient->getCommentaireProspect()) ? $entityClient->getCommentaireProspect() : null,
                'delivryDates' => (null !== $prospectCodeZone) ? $prospectCodeZone : null,
                'franco' => (null !== $currentUser->getMontantFranco()) ? $currentUser->getMontantFranco() : null
            ];

            $arrayValidOptions = array();
            foreach ($prospectOptions as $key => $value) {

                if (null !== $value) {
                    $arrayValidOptions[$key] = $value;
                }
            }

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

        $paramViews = array(
            'commandeEncours' => $this->get('sogedial_integration.catalogue')->getOrdersByUser($multiplyByPcb, 3),
            'commercialInfo' => $em->getRepository('SogedialUserBundle:User')->getCommercialInformation($this->get('sogedial.multisite')->getSociete()),
            'clientInfo' => $clientInfo,
            'entreprise' => $this->get('sogedial.multisite')->getMasterEnterprise(),
            'order' => $command['order'],
            'totalAmount' => $OrderTotalAmount['totalAmount'],
            'form' => $form->createView(),
            'baseUrl' => $this->container->getParameter('baseUrl'),
            'search' => $search,
            'promonews' => $promonews,
            'currentPage' => 'dashboard',
            'listSociete' => $this->get('sogedial_integration.catalogue')->getListSocieteByCodeRegionAndCodeClient($entityClient->getCode()),
            'societe' => $societe,
            'is_prospect' => $isProspect,
            'messageClients' => $messageClients,
            'formattedExpirationDate' => ($isProspect && $rawExpirationDate ? $formattedExpirationDate : null),
            'remainingProspectTime' => ($isProspect && $rawExpirationDate ? $remainingProspectTime : null),
            'prospectOptions' => (empty($arrayValidOptions) ? null : $arrayValidOptions)
        );
        return $this->render('SogedialIntegrationBundle:Dashbord:index.html.twig', $paramViews);
    }

    /**
     * @param $zone
     * @return string
     */
    private function formatListZone($zone)
    {
        $strDays = '';
        if ($zone->getLundi() === true) {
            $strDays .= 'Lundi';
        }

        if ($zone->getMardi() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Mardi';
        }

        if ($zone->getMercredi() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Mercredi';
        }

        if ($zone->getJeudi() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Jeudi';
        }

        if ($zone->getVendredi() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Vendredi';
        }

        if ($zone->getSamedi() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Samedi';
        }

        if ($zone->getDimanche() === true) {
            if (strlen($strDays) > 0) {
                $strDays .= ", ";
            }
            $strDays .= 'Dimanche';
        }

        return $strDays;
    }
}
