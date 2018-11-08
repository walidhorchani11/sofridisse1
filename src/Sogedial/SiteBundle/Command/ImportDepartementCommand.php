<?php
namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Departement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;

class ImportDepartementCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importDepartementCsv',
            'Import departement from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'departement';
        $this->executeCmd($this, $commandeName, $input, $output);        
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        foreach ($data as $row) {
            $codeDepartement = parent::$em->getRepository('SogedialSiteBundle:Departement')
                ->findOneBy(array('code' => $row[0]));

            if ($codeDepartement instanceof Departement) {
                $codeDepartement->setLibelle($row[1]);

                parent::$em->persist($codeDepartement);
            } else {
                $departement = new Departement();
                $departement->setCode($row[0]);
                $departement->setLibelle($row[1]);

                parent::$em->persist($departement);
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
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMDEPAP.CSV', ',');


        if ($data!==false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/DEPART.CSV', ',');

        return $data;
    }
}