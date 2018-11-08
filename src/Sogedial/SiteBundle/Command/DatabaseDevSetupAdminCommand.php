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

class DatabaseDevSetupAdminCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:executeDatabaseDevAdminSetup')
            ->setDescription('Run necessary commands to populate Admin DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        $output->writeln('Running database development update setup ... ');
        $logger->info('Running database development update setup ...');

        $this->importAdmin($input, $output);

        $logger->info('Database development update complete');
        $output->writeln('Database development update complete');
    }

    protected function importAdmin(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $entreprises = $em->getRepository('SogedialSiteBundle:Entreprise')->findAll();

        //Apres install from scratch
        $defaultAdmin = $em->getRepository('SogedialUserBundle:User')->findOneById(1);

        foreach ($entreprises as $key => $entreprise) {
            $precommandExtension = "";
            $precommand = NULL;
            //precommande
            if(strlen($entreprise->getCode()) === 4){
                $subCodeEntreprise = substr($entreprise->getCode(), -1);
                $precommandExtension = ($subCodeEntreprise === '1') ? "-avion" : "-bateau";
                $precommand = ($subCodeEntreprise === '1') ? 1 : 2;
            }

            $admin = $em->getRepository('SogedialUserBundle:User')
                    ->findOneBy(array('entreprise' => $entreprise->getCode(), "username" => "admin-".$entreprise->getEtablissement().$precommandExtension));

            if (!($admin instanceof User)) {
                $admin = clone $defaultAdmin;
                $admin->clearId();
                $admin->setEntreprise($entreprise);
                $admin->setEntrepriseCourante($entreprise->getCode());
            }

            $admin->setEmail("admin-".$entreprise->getEtablissement().$precommandExtension."@gmail.com");
            $admin->setUsername("admin-".$entreprise->getEtablissement().$precommandExtension);
            $admin->setUsernameCanonical("admin-".$entreprise->getEtablissement());
        
            $admin->setPrecommande($precommand);
            $em->persist($admin);
            $em->flush();
        }
    }
}
