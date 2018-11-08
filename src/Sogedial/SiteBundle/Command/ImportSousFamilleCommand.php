<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Entity\SousFamille;
use Sogedial\SiteBundle\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportSousFamilleCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importSousFamilleCsv',
            'Import sous famille from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'sous-famille';
        $this->executeCmd($this, $commandeName, $input, $output);    
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $currentRegion = $this->getRegionNumeric();

        foreach ($data as $row) {
            $famille = parent::$em->getRepository('SogedialSiteBundle:Famille')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1]));    

            if ($row[0]!=="" && $row[0]!==$row[1].$row[3])
            {
                $this->issue($i, $output, "Inconsistent composite field.");
            }

            $sousFamille = parent::$em->getRepository('SogedialSiteBundle:SousFamille')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1].$row[3]));                      // existe-t-il un risque de confusion "AB"."CD" vs. "ABC"."D" ? Cette question s'applique à tous les codes composites.

            if (!($sousFamille instanceof SousFamille))
            {
                $sousFamille = new SousFamille();
                $sousFamille->setCode($currentRegion.'-'.$row[1].$row[3]);
            }

            if ($famille instanceof Famille) {
                $sousFamille->setFamille($famille);
            }

                
                
            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $currentRegion));

            if ($region instanceof Region) {
                $sousFamille->setRegion($region);
            }
            // juste famille, pas de secteur / rayon. les familles sont uniques!
            $sousFamille->setLibelle(utf8_encode(trim($row[2])));
            $sousFamille->setValeur($row[3]);

            parent::$em->persist($sousFamille);
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
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMSFAMP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/SOUSFAM.CSV', ',');

        return $data;
    }
}