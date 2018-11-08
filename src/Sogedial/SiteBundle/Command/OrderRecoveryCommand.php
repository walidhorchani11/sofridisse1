<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderRecoveryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:order-recovery')
            ->setDescription('Récupération de commande par son identifiant')
            ->addArgument(
            'panierId',
            InputArgument::OPTIONAL,
            'Identifiant du panier à reprendre ?'
            )
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $panierId = $input->getArgument('panierId');

        if( $panierId ) {
            $panierObject = $doctrine->getRepository('SogedialSiteBundle:Commande')->findOneBy(array('id' => $panierId));
            $weiredPanierIdList = $doctrine->getRepository('SogedialSiteBundle:Commande')->getListPanierId();

            if($panierObject instanceof Commande && $panierObject->getUser()->getId() === $panierObject->getValidator()->getId()) {
                $orderOrderObject = $doctrine->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneBy(array('order' => $panierObject->getId()));
                if ( !in_array($panierObject->getId(), $weiredPanierIdList) && null !== $orderOrderObject->getOrderStatus()->getKey() && $orderOrderObject->getOrderStatus()->getKey() === 'STATUS_BASKET_PENDING') {
                    $now = new \DateTime();
                    $output->writeln('<comment>Start : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
                    $userObject = $panierObject->getValidator();

                    $dates = null;
                    if ($panierObject->isDatesString() && null !== $panierObject->isDatesString()) {
                        $dates = explode(",", $panierObject->isDatesString());
                    }

                    $comment = str_replace('-', ' ', $panierObject->getCommentaire());

                    //$validator = $this->getContainer()->get('sogedial_integration.commande')->getValidatorCommande($userObject);
                    $validator = $userObject;

                    $ambientDeliveryDate = $positiveColdDeliveryDate = $negativeColdDeliveryDate = null;
                    if($dates && null !== $dates) {
                        $ambientDeliveryDate = $this->getContainer()->get('sogedial_integration.commande')->dateFormatHelper($dates[0]);
                        $positiveColdDeliveryDate = $this->getContainer()->get('sogedial_integration.commande')->dateFormatHelper($dates[1]);
                        $negativeColdDeliveryDate = $this->getContainer()->get('sogedial_integration.commande')->dateFormatHelper($dates[2]);
                    }

                    $orderProducts = $doctrine->getRepository('SogedialSiteBundle:Produit')->getRecapByOrder($panierObject->getId());
                    $entrepriseInfos = $doctrine->getRepository('SogedialSiteBundle:Commande')->getEntrepriseInfosForRecapByOrder($panierObject->getId());
                    $clientInfos = $doctrine->getRepository('SogedialSiteBundle:Commande')->getClientInfosForRecapByOrder($panierObject->getId());
                    $validatingDate = $this->getContainer()->get('sogedial_integration.commande')->getValidatingDate();

                    $productsLignesCommandes = $this->getContainer()->get('sogedial_integration.commande')->setLigneCommandesForConsole($orderProducts, $clientInfos, $panierObject, $userObject);
                    $this->getContainer()->get('sogedial_integration.commande')->createSubOrders($userObject, $panierObject, $productsLignesCommandes, array_keys($productsLignesCommandes), $comment, $validator, $validatingDate, $now, $now, $ambientDeliveryDate, $positiveColdDeliveryDate, $negativeColdDeliveryDate);

                    //Generate AS400 file
                    $this->getContainer()->get('sogedial_as400.commande.file')->handleFile($productsLignesCommandes, $clientInfos, $panierObject->getId(), $entrepriseInfos, $comment, $userObject->getPreCommande() !== NULL);

                    //Update orderStatus
                    $this->getContainer()->get('sogedial_integration.commande')->updateOrderStatus($panierObject, 'STATUS_BASKET_VALIDATED', $now, $now);

                    $end = new \DateTime();
                    $output->writeln('<comment>END : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');

                } else {
                    throw new NotFoundHttpException('le panier en question a dejà ete traité');
                }
            }

        } else {
            die('pas de panier');
        }

    }
}