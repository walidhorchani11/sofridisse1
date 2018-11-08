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
class DatabaseDevSetupZoneCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sogedial:executeDatabaseDevZoneSetup')
            ->setDescription('Run necessary commands to populate Admin DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        $output->writeln('Running database development update setup ... ');
        $logger->info('Running database development update setup ...');

        $this->importZone($input, $output);

        $logger->info('Database development update complete');
        $output->writeln('Database development update complete');
    }

    protected function importZone(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getZone($input, $output);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $size = count($data);
        $batchSize = 20;
        $i = 1;
        $progress = new ProgressBar($output, $size);
        $progress->start();
        $entreprises = array();

        foreach ($data as $row) {
            //$zone = $em->getRepository('SogedialSiteBundle:Zone')
            //    ->findOneBy(array('code' => $row[0]));
            $zone = $em->getRepository('SogedialSiteBundle:Zone')
                ->findOneBy(array('temperature' => $row[10], 'entreprise' => $row[1]));

            if(!isset($entreprises[$row[1]])){
                $entreprises[$row[1]] = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $row[1]));
                var_dump($entreprises[$row[1]]->getCode());
            }

            if (!($zone instanceof Zone)) {
                $zone = new Zone();
                $zone->setEntreprise($entreprises[$row[1]]);
                $zone->setTemperature($row[10]);
                // ATTENTION = l'id sera associé automatiquement et ne sera donc pas nécessairement l'id demandé
            }

            $zone->setNom($row[2]);
            $zone->setLundi($row[3]);
            $zone->setMardi($row[4]);
            $zone->setMercredi($row[5]);
            $zone->setJeudi($row[6]);
            $zone->setVendredi($row[7]);
            $zone->setSamedi($row[8]);
            $zone->setDimanche($row[9]);

            $em->persist($zone);

            if (($i % $batchSize) === 0) {
                //$em->flush();
                //$em->clear();

                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of zone imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }
        $em->flush();
        $em->clear();

        $progress->finish();
        $output->writeln('');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function getZone(InputInterface $input, OutputInterface $output)
    {
        $data = array();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $query = "SET FOREIGN_KEY_CHECKS=0; TRUNCATE zone; ALTER TABLE zone AUTO_INCREMENT = 1;";
        $em->getConnection()->exec($query);

        $entreprises = $em->getRepository('SogedialSiteBundle:Entreprise')->findAll();
        $tmpKey = 0;
        foreach([ "SEC", "FRAIS", "SURGELE"] as $temperature){
            foreach ($entreprises as $key => $entreprise) {
                $data[] = array(
                    $tmpKey++,
                    $entreprise->getCode(),
                    $entreprise->getCode().' Lundi mercredi vendredi',
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    rand(0,1) == 1,
                    $temperature
                );
            }
        }
        // ATTENTION = l'id sera associé automatiquement et ne sera donc pas nécessairement l'id demandé
        // NE CHANGEZ jamais les ids !! (vous ne pouvez qu'en ajouter des nouveaux)
        // De toute façon, ce n'est pas une bonne chose à faire car cela pourrait changer les zones des utilisateurs déjà configurés
        // Respectez l'ordre !!
        // Lorsque vous videz la table, remettez AUTO-ICREMENT à 1
        // $data[] = array(1, 110, '110 Lundi mercredi vendredi', 1, 0, 1, 0, 1, 0, 0);
        // $data[] = array(2, 120, '120 Mardi jeudi samedi', 0, 1, 0, 1, 0, 1, 0);
        // $data[] = array(3, 130, '130 Mardi jeudi samedi', 0, 1, 0, 1, 0, 1, 0);
        // $data[] = array(4, 222, '222 Lundi mercredi vendredi', 1, 0, 1, 0, 1, 0, 0);
        // $data[] = array(5, 240, '240 Lundi mercredi vendredi', 1, 0, 1, 0, 1, 0, 0);
        // $data[] = array(6, 301, '301 Lundi mercredi vendredi', 1, 0, 1, 0, 1, 0, 0);

        return $data;
    }
}
