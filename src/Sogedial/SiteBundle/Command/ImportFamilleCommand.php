<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Entity\Rayon;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportFamilleCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importFamilleCsv',
            'Import famille from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'famille';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $currentRegion = $this->getRegionNumeric();
        $region = parent::$em->getRepository('SogedialSiteBundle:Region')
        ->findOneBy(array('code' => $currentRegion));

        foreach ($data as $row) {
            if ( $this->getRegion() === 'region3' ) {
                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    ->findOneBy(array('valeur' => $row[5], 'region' => intval($region->getCode())));                            // on prend le premier, bien qu'il peut y en avoir plusieurs ! Voir commentaire dans import-mapping-unifié.xlsx
            } else {
                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    //->findOneBy(array('valeur' => $row[5], 'region' => intval($region->getCode())));                            // on prend le premier, bien qu'il peut y en avoir plusieurs ! Voir commentaire dans import-mapping-unifié.xlsx
                    ->findOneBy(array('code' => $currentRegion.'-'.$row[5]));
            }

            $codeFamille = parent::$em->getRepository('SogedialSiteBundle:Famille')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1]));                              // attention : *juste* la famille, pas rayon+famille ni secteur+rayon+famille, est utilisée en tant que clef

            if ($codeFamille instanceof Famille) {
                $codeFamille->setLibelle(utf8_encode(trim($row[2])));

                if ($rayon instanceof Rayon) {
                    $codeFamille->setRayon($rayon);
                }

                parent::$em->persist($codeFamille);
            } else {
                $famille = new Famille();
                $famille->setCode($currentRegion.'-'.$row[1]);

                if ($rayon instanceof Rayon) {
                    $famille->setRayon($rayon);
                }
                $famille->setLibelle(utf8_encode(trim($row[2])));

                parent::$em->persist($famille);
            }

            parent::advance($i, $output);
            $i++;
        }
        $this->finish();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMFAMIP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/FAMILLE.CSV', ',');

        return $data;
    }
}