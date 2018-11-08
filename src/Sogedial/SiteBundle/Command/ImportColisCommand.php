<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Colis;
use Sogedial\SiteBundle\Entity\Produit;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportColisCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importColisCsv',
            'Import colis from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'colis';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 1;
        $skipped = 0;
        $societe_site = $this->getSocieteSite();
        foreach ($data as $row) {
            $societeAlpha = substr($row[30], 0, 3);
            $societeNum = parent::$ms->getSocieteNumByAlpha($societeAlpha);
            //$codeColis = parent::$em->getRepository('SogedialSiteBundle:Colis')->findOneBy(array('code' => $row[30]));
            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')->findOneBy(array('code' => $societeAlpha . '-' . $row[0])); // on extrait code region + code société numérique à deux chiffres

            $colis = new Colis();
            $colis->setCode($row[30]);
            $colis->setEan($row[16]);
            $colis->setPoidsBrutUVC($row[2]);
            $colis->setPoidsNetUVC($row[3]);
            $colis->setPoidsBrutColis($row[4]);
            $colis->setVolumeColis($row[5]);
            $colis->setCouchePalette($row[6]);
            $colis->setColisCouche($row[7]);
            $colis->setCommercialUnityNumber($row[9]);
            $colis->setCommercialUnityType($row[10]);
            $colis->setCommercialUnityDescription($row[11]);
            $colis->setVolumeUc($row[25]);
            $colis->setLongerDimensionsUc($row[22]);
            $colis->setLargerDimensionsUc($row[23]);
            $colis->setHeightDimensionsUc($row[24]);

            if ($produit instanceof Produit) {
                $colis->setProduit($produit);
            }
            parent::$em->merge($colis);
            parent::advance($i, $output);
            $i++;
        }

        $this->setSkipped($skipped);
        $this->finish();
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

        if ($this->getRegion() === 'region3') {
            $data = array();
        } else {
            $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMCOLIP.CSV', ',');

            if ($data !== false && $data !== null) {
                return $data;
            }

            $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/COLIS.CSV', ',');
        }

        return $data;
    }
}