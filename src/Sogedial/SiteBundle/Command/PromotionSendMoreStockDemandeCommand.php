<?php

namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PromotionSendMoreStockDemandeCommand extends ContainerAwareCommand {

  protected function configure() {
    $this
        ->setName('sogedial-promotion:moreStockDemandeEmail')
        ->setDescription('Send email EF');
  }

  protected function execute(InputInterface $input, OutputInterface $output){
    $clientsPromotions = $this->getContainer()->get('sogedial.promotion')->getStockEngagementDemandeEmailByClients();
    //Attention ici lorsque l'on voudra appeler cette fonction pour plusieurs société il faudra les passer en parametre.
    $this->getContainer()->get('sogedial.export')->sendStockEngagementDemandeEmails($clientsPromotions, 222);
    $this->getContainer()->get('doctrine')->getRepository('SogedialSiteBundle:Promotion')->resetStockEngagementDemand();
  }
}