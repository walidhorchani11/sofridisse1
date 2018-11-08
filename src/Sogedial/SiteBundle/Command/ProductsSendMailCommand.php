<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProductsSendMailCommand extends ContainerAwareCommand
{

    protected $user = null;
    protected $to = '';
    protected $from = '';
    protected $body = '';
    protected $filepath = '';
    protected $subject = '';

    protected function configure()
    {
        $this
            ->setName('sogedial-products:sendMail')
            ->setDescription('Send mails.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $commandeRepository = $doctrine->getRepository('SogedialSiteBundle:Commande');
        $commandesNeedRecept = $commandeRepository->getCommandesNeedReceptEmailWithClient();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $command = null;
        $client = null;
        $i = 0;

        foreach ($commandesNeedRecept as $key => $commandeNeedRecept) {
            if (array_key_exists("commande", $commandeNeedRecept)) {
                $command = $commandeNeedRecept["commande"];
                $i = $key;
            }
            if (array_key_exists("client", $commandeNeedRecept)) {
                $client = $commandeNeedRecept["client"];
            }

            if ($command !== null && $client !== null) {
                $commercial = $doctrine->getRepository('SogedialUserBundle:User')->getCommercialInformation($command->getEntreprise()->getCode());
                $this->getContainer()->get('sogedial.export')->sendFrancoMail($command->getNumero(), $command->getMontantCommande(), $client->getNom(), $command->getCommentaire(), $commercial["email"], $commercial["entrepriseCourante"]);
                $commandesNeedRecept[$i]["commande"]->setReceptEmail(true);
                $em->persist($commandesNeedRecept[$i]["commande"]);
                $command = null;
                $client = null;
            }
        }
        $em->flush();
    }

}