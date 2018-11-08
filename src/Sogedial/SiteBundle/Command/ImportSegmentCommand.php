<?php

namespace Sogedial\SiteBundle\Command;


use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Entity\SousFamille;
use Sogedial\SiteBundle\Entity\Segment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportSegmentCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importSegmentCsv',
            'Import segment from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'segment';
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

            $compositeCode=$row[0].$row[1].$row[2];

            if ($row[4]!=="" && $row[4]!==$compositeCode)
            {
                $this->issue($i, $output, "Inconsistent composite field.");
            }

            $segment = parent::$em->getRepository('SogedialSiteBundle:Segment')
                ->findOneBy(array('code' => $compositeCode));

            $sousFamille = parent::$em->getRepository('SogedialSiteBundle:SousFamille')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[0].$row[1]));

            if (!($segment instanceof Segment))
            {
                $segment = new Segment();
                $segment->setCode($compositeCode);
            }

            if ($sousFamille instanceof SousFamille) {
                $segment->setSousFamille($sousFamille);
            }
            $segment->setLibelle(utf8_encode($row[3]));
            $segment->setValeur($row[2]);

            parent::$em->persist($segment);
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
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMSEGMP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/SEGMENT.CSV', ',');

        return $data;
    }
}