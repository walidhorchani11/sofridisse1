<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductsCreatePDFCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sogedial-products:preparePDF')
            ->setDescription('Create PDF receipt\'s order');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();


        $commandeRepository = $doctrine->getRepository('SogedialSiteBundle:Commande');
        $commandesNeedRecept = $commandeRepository->getCommandesWithClientNewVersion();

        foreach ($commandesNeedRecept as $key => $commandeNeedRecept) {
            $client = $commandeNeedRecept["client"];
            $command = $commandeNeedRecept["commande"];
            $commercial = $doctrine->getRepository('SogedialUserBundle:User')->getCommercialInformation($command->getEntreprise()->getCode());
            $pdfGenerateresult = $this->getContainer()->get('sogedial.export')->generatePdfForOrder($command, $client);
            if($pdfGenerateresult) {
                $commandesNeedRecept[$key]["commande"]->setReceptPDF(true);
                $emailSentresult = $this->getContainer()->get('sogedial.export')->sendFrancoMail($command->getNumero(), $command->getMontantCommande(), $client->getNom(), $command->getCommentaire(), $commercial["email"], $commercial["entrepriseCourante"]);
                if($emailSentresult) {
                    $commandesNeedRecept[$key]["commande"]->setReceptEmail(true);
                }

                $em->persist($commandesNeedRecept[$key]["commande"]);
                $em->flush();
            }
        }

    }
}