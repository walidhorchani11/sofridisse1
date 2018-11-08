<?php


namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Enseigne;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;


class ImportEnseigneCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importEnseigneCsv',
            'Import enseigne from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'enseigne';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $currentRegion = $this->getRegionNumeric();

        foreach ($data as $row) {
            $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')->findOneBy(array('code' => $currentRegion.'-'.$row[0]));

            if (!($enseigne instanceof Enseigne)) {
                $enseigne = new Enseigne();
                $enseigne->setCode($currentRegion.'-'.$row[0]);
            }

            $enseigne->setLibelle(utf8_encode($row[1]));
            parent::$em->persist($enseigne);
            parent::advance($i, $output);
            $i++;
        }
        $this->finish();
        // vérification de l'existence de "SE$"
        $ajout_se = false;
        $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')->findOneBy(array('code' => $currentRegion.'-'."SE$"));

        if (!($enseigne instanceof Enseigne)) {
            $enseigne = new Enseigne();
            $enseigne->setCode($currentRegion.'-'."SE$");
            $enseigne->setLibelle("SANS ENSEIGNE");

            parent::$em->persist($enseigne);
            $ajout_se = true;
        }

        $this->finish();

        $output->writeln('');
        $output->writeln('Info: "'.$currentRegion.'-'.'SE$" (SANS ENSEIGNE)' . ($ajout_se ? ' was added.' : ' existed already.'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMENSEP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/ENSEIGNE.CSV', ',');

        return $data;
    }
}