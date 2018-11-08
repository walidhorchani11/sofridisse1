<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\UserBundle\Entity\User;



// il existe 3 types de données :
// - les données provenant de AS400 -> ingestion classique
// - les données "fixes" nécessaires au fonctionnement des sites e-commerce -> setupInternalTables()
// - les données de configuration et d'activité :
//    - les utilisateurs actifs et leurs configuration
//    - les commandes
//    - la configuration générale du site, comme les zones -> nous les configurons dans ce fichier,
//      ce qui est utile pour le développement, mais il ne faut pas les laisser dans l'ingestion classique
//      au risque d'écraser avec des données statiques une véritable configuration.

class DatabaseDevSetupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:executeDatabaseDevSetup')
            ->setDescription('Run necessary commands to populate DB with data useful for development');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        $output->writeln('Running database development update setup ... ');
        $logger->info('Running database development update setup ...');

        $this->sub_execute($input, $output);

        $logger->info('Database development update complete');
        $output->writeln('Database development update complete');
    }

    private function sub_execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln('<comment>Start : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');

        $this->getApplication()->find('sogedial:executeDatabaseDevZoneSetup')->run($input, $output);
        $this->getApplication()->find('sogedial:executeDatabaseDevAdminSetup')->run($input, $output);

        $now = new \DateTime();
        $output->writeln('<comment>End : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');        
    }
}
