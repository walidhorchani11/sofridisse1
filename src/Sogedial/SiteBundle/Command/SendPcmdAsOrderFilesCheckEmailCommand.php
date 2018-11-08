<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPcmdAsOrderFilesCheckEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:send-pcmd-as400-order-files-check-email')
            ->setDescription('Envoie par mail la liste des commandes pcmd dont le fichier AS400 est absent dans le répertoire source')
        ;

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln($now->format('d-m-Y G:i:s') . '<comment> : Début du traitement  ---</comment>');

        $doctrine = $this->getContainer()->get('doctrine');
        $pcmdOrderListSinceLastWeek = $doctrine->getRepository('SogedialSiteBundle:Commande')->getListPcmdOrdersSinceLastWeek();

        if(null !== $pcmdOrderListSinceLastWeek) {
            $this->getContainer()->get('sogedial.export')->checkPcmdOrdersFiles($pcmdOrderListSinceLastWeek);
        }

        $now = new \DateTime();
        $output->writeln($now->format('d-m-Y G:i:s') . '<comment> : Fin du traitement  ---</comment>');

    }
}