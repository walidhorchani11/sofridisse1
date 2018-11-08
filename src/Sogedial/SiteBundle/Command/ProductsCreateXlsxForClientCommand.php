<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductsCreateXlsxForClientCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sogedial-products:prepareXlsxForClient')
            ->setDescription('Create XLSX receipt\'s order for client');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $commandeRepository = $doctrine->getRepository('SogedialSiteBundle:Commande');
        $commandesNeedRecept = $commandeRepository->getCommandesWithClientForEmailing();

        foreach ($commandesNeedRecept as $key => $commandeNeedRecept) {
            $client = $commandeNeedRecept["client"];
            $command = $commandeNeedRecept["commande"];

            $xlsxGenerateresult = $this->getContainer()->get('sogedial.export')->generateXlsxForOrder($command, $client);

            if($xlsxGenerateresult && $client->isIsRecipient()) {
                $commandesNeedRecept[$key]["commande"]->setReceptClientXlsx(true);
                $emailSentresult = $this->getContainer()->get('sogedial.export')->sendFrancoMailForClient($command->getNumero(), $command->getMontantCommande(), $client->getResponsable1(), $command->getCommentaire(), $client->getEmail());
                if($emailSentresult) {
                    $commandesNeedRecept[$key]["commande"]->setReceptClientEmail(true);
                }

                $em->persist($commandesNeedRecept[$key]["commande"]);
                $em->flush();
            }
        }

    }
}