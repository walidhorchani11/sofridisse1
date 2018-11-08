<?php

namespace Sogedial\SiteBundle\Command;


use Sogedial\SiteBundle\Entity\Secteur;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;

class ImportSecteurCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importSecteurCsv',
            'Import secteur from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'secteurs';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $currentRegion = $this->getRegionNumeric();

        foreach ($data as $row) {
            if ($row[1] !== 'TRANSPORT') {
                $codeSecteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                    ->findOneBy(array('code' => $currentRegion.'-'.$row[0]));

                if ($codeSecteur instanceof Secteur) {
                    $codeSecteur->setLibelle($row[1]);
                    $codeSecteur->setCreatedAt(new \DateTime('now'));

                    parent::$em->persist($codeSecteur);
                } else {
                    $secteur = new Secteur();
                    $secteur->setCode($currentRegion.'-'.$row[0]);
                    $secteur->setLibelle($row[1]);
                    $secteur->setCreatedAt(new \DateTime('now'));

                    parent::$em->persist($secteur);
                }
            }
            parent::advance($i, $output);

            $i++;
        }
        $this->finish();
        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMDSECTP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/SECTEUR.CSV', ',');

        return $data;
    }
}