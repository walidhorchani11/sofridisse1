<?php


namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Secteur;
use Sogedial\SiteBundle\Entity\Rayon;
use Sogedial\SiteBundle\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportRayonCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importRayonCsv',
            'Import rayon from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'rayon';
        $this->executeCmd($this, $commandeName, $input, $output);    
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $currentRegion = $this->getRegionNumeric();
        /*
        $data = $this->get($input, $output);
        if($data == FALSE){
            $output->writeln('File not found, skipping.');
            return 0;
        }
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();
        */
        $i = 0;
        $skipped = 0;
        foreach ($data as $row) {
            $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1]));

            if ($row[0]!=="" && $row[0]!==$row[1].$row[3]){
                $this->issue($i, $output, "Inconsistent composite field.");
            }

            $codeRayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1].$row[3]));


            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $currentRegion));

            if ($codeRayon instanceof Rayon) {
                if ($secteur instanceof Secteur) {
                    $codeRayon->setSecteur($secteur);
                }
                $codeRayon->setLibelle(utf8_encode(trim($row[2])));
                $codeRayon->setValeur($row[3]);

                if ($region instanceof Region) {
                    $codeRayon->setRegion($region);
                }

                parent::$em->persist($codeRayon);
            } else {
                $rayon = new Rayon();
                $rayon->setCode($currentRegion.'-'.$row[1].$row[3]);
                if ($secteur instanceof Secteur) {
                    $rayon->setSecteur($secteur);
                }
                $rayon->setLibelle(utf8_encode(trim($row[2])));
                $rayon->setValeur($row[3]);
                
                if ($region instanceof Region) {
                    $rayon->setRegion($region);
                }

                parent::$em->persist($rayon);
            }
            parent::advance($i, $output);

            $i++;
        }

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
        $fileName = 'web/uploads/import/' . $this->getRegion() . '/PMRAYOP.CSV';
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert($fileName, ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/RAYON.CSV', ',');

        return $data;
    }
}