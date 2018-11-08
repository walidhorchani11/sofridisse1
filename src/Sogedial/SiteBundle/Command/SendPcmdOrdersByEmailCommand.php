<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPcmdOrdersByEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:send-pcmd-orders')
            ->setDescription('Envoie la liste des commandes PCMD par mail')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln($now->format('d-m-Y G:i:s') . '<comment> : Début du traitement  ---</comment>');

        $doctrine = $this->getContainer()->get('doctrine');

        $pcmdOrderList = $doctrine->getRepository('SogedialSiteBundle:Commande')->getListPcmdOrdersToSend();

        if(null !== $pcmdOrderList) {
            $this->getContainer()->get('sogedial.export')->generateExcelForPcmdOrders($pcmdOrderList);
        }

        $now = new \DateTime();
        $output->writeln($now->format('d-m-Y G:i:s') . '<comment> : Fin du traitement  ---</comment>');

    }
}