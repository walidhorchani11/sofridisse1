<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportStockCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importStockCsv',
            'Import stock from CSV file');
    }

    // Attention, cette commande peut être appelée soit de DatabaseSetup, soit de DatabaseStockUpdate
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'stocks';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line ' . $lineno . ' - ' . $msg);
    }

    protected function import($data, OutputInterface $output)
    {

        $i = 1;
        $skipped = 0;
        $critical = 0;
        $societe_site = $this->getSocieteSite();

        foreach ($data as $row) {
            $stockColis = trim($row[4]);
            $stockUc = trim($row[5]);

            if ($row[2] === '301' && substr($stockColis, 0, 3) === ',00') {
                $skipped++;
                continue;
            }
            if ($row[2] === '301' && substr($stockUc, 0, 3) === ',00') {
                $skipped++;
                continue;
            }

            // si ça commence par "." => Javascript ne supporte pas ce format
            if (substr($stockColis, 0, 1) === '.') {
                $stockColis = "0" . $stockColis;
            }
            if (substr($stockUc, 0, 1) === '.') {
                $stockUc = "0" . $stockUc;
            }

            // Dans le cas où le stockColis ou le stockUc commence par
            if (substr($stockColis, 0, 1) === ',') {
                $stockColis = str_replace(',', '.', $stockColis);
                $stockColis = "0" . $stockColis;
            }
            if (substr($stockUc, 0, 1) === ',') {
                $stockUc = str_replace(',', '.', $stockUc);
                $stockUc = "0" . $stockUc;
            }

            if ($row[0] !== "" && $row[0] !== $row[2] . $row[3]) {
                $this->issue($i, $output, "Inconsistent composite field.");
            }

            $stock = parent::$em->getRepository('SogedialSiteBundle:Stock')
                ->findOneBy(array('code' => $row[2] . $row[3]));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => substr($row[2], 0, 1)));

            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $row[2] . '-' . $row[3]));                  // code région + code entreprise numérique à 2 chiffres + '-' + code produit

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[2]));

            if (!($stock instanceof Stock)) {
                $stock = new Stock();
                $stock->setCode($row[2] . $row[3]);
            }

            if ($entreprise instanceof Entreprise) {
                $stock->setEntreprise($entreprise);
            }

            if ($region instanceof Region) {
                $stock->setRegion($region);
            }

            if ($produit instanceof Produit) {
                $stock->setProduit($produit);
            }


            if (floatval($stockColis) < 0) {
                $this->issue($i, $output, "Invalid value for 'stockColis': '" . $stockColis . "'");
            }
            if (floatval($stockUc) < 0) {
                $this->issue($i, $output, "Invalid value for 'stockColis': '" . $stockUc . "'");
            }


            $stock->setStockTheoriqueColis($stockColis);
            $stock->setStockTheoriqueUc($stockUc);

            parent::$em->persist($stock);
            parent::advance($i, $output);
            $i++;
        }
        $this->finish();

        $output->writeln('');
        //$output->writeln($skipped.' "stock" entries skipped.' );
        $output->writeln(($critical ? $critical : "No") . ' critical errors in "stock" entries.');
        return $skipped;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMSTOKP.CSV', ',');

        if ($data !== false && $data !== null) {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/STOCK.CSV', ',');

        return $data;
    }
}