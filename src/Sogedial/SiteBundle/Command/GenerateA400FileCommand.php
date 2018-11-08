<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GenerateA400FileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:regenerate-as400file')
            ->setDescription("Régénerer le fichier AS400 d'un commande validé ")
            ->addArgument(
                'orderId',
                InputArgument::OPTIONAL,
                'Identifiant de la commande à traiter ?'
            )
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $orderId = $input->getArgument('orderId');

        if( $orderId ) {
            $orderObject = $doctrine->getRepository('SogedialSiteBundle:Commande')->findOneBy(array('id' => $orderId));
            $panierId = $orderObject->getParent();
            $panierObject = $doctrine->getRepository('SogedialSiteBundle:Commande')->findOneBy(array('id' => $panierId));

            if( $orderObject instanceof Commande ) {

                $orderOrderObject = $doctrine->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneBy(array('order' => $orderObject->getId()));

                if ( null !== $orderOrderObject->getOrderStatus()->getKey() && $orderOrderObject->getOrderStatus()->getKey() === 'STATUS_APPROVED') {
                    $now = new \DateTime();
                    $output->writeln('<comment>Start : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');

                    $userObject = $orderObject->getUser();
                    $comment = str_replace('-', ' ', $orderObject->getCommentaire());
                    $orderProducts = $doctrine->getRepository('SogedialSiteBundle:Produit')->getRecapByOrder($panierId);
                    $entrepriseInfos = $doctrine->getRepository('SogedialSiteBundle:Commande')->getEntrepriseInfosForRecapByOrder($orderObject->getId());
                    $clientInfos = $doctrine->getRepository('SogedialSiteBundle:Commande')->getClientInfosForRecapByOrder($orderObject->getId());
                    $productsLignesCommandes = $this->getContainer()->get('sogedial_integration.commande')->setProductsPricingForConsole($orderProducts, $clientInfos, $panierObject, $userObject);

                    //Generate AS400 file
                    $this->getContainer()->get('sogedial_as400.commande.file')->handleFile($productsLignesCommandes, $clientInfos, $panierId, $entrepriseInfos, $comment, $userObject->getPreCommande() !== NULL);

                    $end = new \DateTime();
                    $output->writeln('<comment>END : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');

                } else {
                    throw new NotFoundHttpException('le panier en question a dejà ete traité');
                }
            }

        } else {
            die('aucun identifiant de commande fourni');
        }

    }
}