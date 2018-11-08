<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductsMetiSendMailRefCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sogedial-productsmeti:sendMailRef')
            ->setDescription('Send mails for meti missing reference in order.')
            ->addArgument(
                'nbDays',
                InputArgument::OPTIONAL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $i = 0;
        
        $commandes = $em->getRepository('SogedialSiteBundle:Commande')->getListCommandWithNotReferecedProducts();
        $productsUnreferencedByClients = array();
        
        foreach ((array)$commandes as $key => $commande) {
            $productsUnreferenced = $em->getRepository('SogedialSiteBundle:ProduitMeti')->getUnreferencedProductByCommandId($commande->getId());

            foreach ((array)$productsUnreferenced as $key => $productUnreferenced) {
                if (!(array_key_exists($productUnreferenced["client_as400"], $productsUnreferencedByClients))){
                    $productsUnreferencedByClients[$productUnreferenced["client_as400"]] = array ();
                }
                if(!(array_key_exists($productUnreferenced["produit_as400"], $productsUnreferencedByClients[$productUnreferenced["client_as400"]]))){
                    $productsUnreferencedByClients[$productUnreferenced["client_as400"]][$productUnreferenced["produit_as400"]] = $productUnreferenced;
                }
            }
        }

        foreach ($productsUnreferencedByClients as $key => $productsUnreferencedByClient) {
            if(sizeof($productsUnreferencedByClient) > 0){
                $this->getContainer()->get('sogedial.export')->sendDemandeRerencementMetiEmails($productsUnreferencedByClient);
            }
        }
    }
}