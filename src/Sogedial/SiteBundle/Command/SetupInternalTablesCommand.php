<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\OrderStatus;
use Sogedial\SiteBundle\Entity\ProduitCompteur;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;
use Sogedial\SiteBundle\Service\UserInfo;

class SetupInternalTablesCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:setupInternalTables',
            'Setup constant tables'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln('<comment>Start (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');

        $this->importEtatCommande($input, $output);
        $this->importRegion($input, $output);
        $this->importEntreprise($input, $output); //TODO : pay attention to updating the entreprise information @Sictoz

        $this->setup($input, $output, 0, "internal tables");
        $this->setSkipped(-1);

        $now = new \DateTime();
        $output->writeln('<comment>End (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    protected function importEtatCommande(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getEtatCommande($input, $output);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach ($data as $row) {
            $etatCommande = $em->getRepository('SogedialSiteBundle:OrderStatus')
                ->findOneBy(array('id' => $row[0]));

            if (!($etatCommande instanceof OrderStatus)) {

                $etatCommande = new OrderStatus();
                // ATTENTION = l'id sera associé automatiquement et ne sera donc pas nécessairement l'id demandé
            }

            $etatCommande->setKey($row[1]);
            $etatCommande->setLibelle($row[2]);

            $em->persist($etatCommande);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();

                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of etatCommande imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        $em->flush();
        $em->clear();

        $progress->finish();
        $output->writeln('');

    }

    protected function importRegion(InputInterface $input, OutputInterface $output)
    {
        $data = $this->getRegionCsv($input, $output);

        $multiSiteService = $this->getContainer()->get('sogedial.multisite');

        if ($data === false) {
            $this->setSkipped(-1);
            $output->writeln('File not found, using hardcoded values for region.');
            $data = $multiSiteService->getFallbackRegionCsv();
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach ($data as $row) {
            $region = $em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[0]));

            if (!($region instanceof Region)) {

                $region = new Region();
                $region->setCode($row[0]);
            }

            $region->setNom(utf8_encode($row[1]));
            $region->setCreatedAt(new \DateTime('NOW'));

            $em->persist($region);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();

                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of region imported ... | ' . $now->format('d-m-Y G:i:s'));
            }
            $i++;
        }

        $em->flush();
        $em->clear();

        $progress->finish();
        $output->writeln('');

    }

    protected function importEntreprise(InputInterface $input, OutputInterface $output)
    {
        $data = false; //TODO : to be reactivated when necessary @Sictoz ($this->getEntreprise($input, $output));

        $multiSiteService = $this->getContainer()->get('sogedial.multisite');

        if ($data === false) {
            $this->setSkipped(-1);
            $output->writeln('File not found, using hardcoded values for entreprise.');
            $data = $multiSiteService->getFallbackEnterpriseCsv();
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach ($data as $row) {
            $entreprise = $em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[1] . $row[2]));

            $region = $em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[1]));

            if (!($entreprise instanceof Entreprise)) {

                $entreprise = new Entreprise();
                $entreprise->setCode($row[1] . $row[2]);
            }

            if ($region instanceof Region) {
                $entreprise->setRegion($region);
            }
            $entreprise->setValeur($row[2]);
            $entreprise->setRaisonSociale(utf8_encode($row[3]));
            $entreprise->setAdresse1($row[4]);
            $entreprise->setAdresse2($row[5]);
            $entreprise->setCodePostal($row[6]);
            $entreprise->setVille($row[7]);
            $entreprise->setPays(utf8_encode($row[8]));
            $entreprise->setActif(1);           // $row[9] // la liste fournie contenait les champs vides dans cette colonne

            if ($row[10] == 0) {    // null in CSV
                $entreprise->setDateDebutActivite(null);
            } else {
                $entreprise->setDateDebutActivite($row[10]);
            }

            if ($row[11] == 0) {
                $entreprise->setDateFinActivite(null);
            } else {
                $entreprise->setDateFinActivite($row[11]);
            }

            $entreprise->setTelephone(isset($row[12]) ? $row[12] : '');
            $entreprise->setFax(isset($row[13]) ? $row[13] : '');
            $entreprise->setEtablissement($row[0]);
            $entreprise->setCreatedAt(new \DateTime('NOW'));
            $entreprise->setNomEnvironnement($row[16]);

            $em->persist($entreprise);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();

                $progress->advance($batchSize);

                $now = new \DateTime();
                $output->writeln(' of entreprise imported ... | ' . $now->format('d-m-Y G:i:s'));
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
     **/
    protected function getRegionCsv(InputInterface $input, OutputInterface $output)
    {

        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/REGION.CSV', ',');

        return $data;

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function getEntreprise(InputInterface $input, OutputInterface $output)
    {
        // cette fonction est séparée au cas où nous voulons charger les données à partir de CSV un jour

        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMENTRP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/ENTREPRI.CSV', ',');

        return $data;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function getEtatCommande(InputInterface $input, OutputInterface $output)
    {
        $data = array();

        // ATTENTION = l'id sera associé automatiquement et ne sera donc pas nécessairement l'id demandé
        // NE CHANGEZ jamais les ids !! (vous ne pouvez qu'en ajouter des nouveaux)
        // De toute façon, ce n'est pas une bonne chose à faire car cela pourrait changer le status des commandes déjà passées
        // Respectez l'ordre !!
        // Lorsque vous videz la table, remettez AUTO-ICREMENT à 1
        $data[] = array(1, 'STATUS_CURRENT', 'En cours');
        $data[] = array(2, 'STATUS_PENDING', 'En attente de validation');
        $data[] = array(3, 'STATUS_APPROVED', 'Validée');
        $data[] = array(4, 'STATUS_PROCESSED', 'Traitée');
        $data[] = array(5, 'STATUS_DELETED', 'Panier supprimé');
        $data[] = array(6, 'STATUS_BASKET_VALIDATED', 'Panier validé');
        $data[] = array(7, 'STATUS_PENDING_AS400', 'En attente'); //not used
        $data[] = array(8, 'STATUS_PENDING_PREPARE', 'En cours de préparation');
        $data[] = array(9, 'STATUS_FACTURED', 'Facturé');
        $data[] = array(10, 'STATUS_REJECTED', 'Rejeté');
        $data[] = array(11, 'STATUS_BASKET_PENDING', 'Panier en attente de validation');
        $data[] = array(12, 'STATUS_ORDER_TO_BE_REVALIDATE', 'Commande à revalider');

        return $data;
    }

}
