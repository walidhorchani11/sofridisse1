<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\JoursFeries;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Sogedial\SiteBundle\Command\ImporterCommands;

class ImportDefaultHolidaysCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:setupDefaultHolidays',
            'Setup constant tables'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln('<comment>Start (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');

        $this->importHolidays($input, $output);

        $this->setup($input, $output, 0, "internal tables");
        $this->setSkipped(-1);

        $now = new \DateTime();
        $output->writeln('<comment>End (internal tables) : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    protected function importHolidays(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $data = $this->getDefaultHolidays($input, $output);

        $size = count($data);
        $batchSize = 20;
        $i = 1;

        $progress = new ProgressBar($output, $size);
        $progress->start();

        foreach ($data as $row) {

            $societe = $em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[0]));

            if (!($societe instanceof Entreprise)) {
                $societe = null;
            }

            $holiday = new JoursFeries();
            $holiday->setMonthNumber($row[1]);
            $holiday->setDayNumber($row[2]);
            $holiday->setEntreprise($societe);

            $em->persist($holiday);

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


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function getDefaultHolidays(InputInterface $input, OutputInterface $output)
    {
        $data = array();

        //Société 110
        $data[] = array(110, 1, 1);
        $data[] = array(110, 12, 25);
        $data[] = array(110, 5, 1);
        $data[] = array(110, 5, 8);
        $data[] = array(110, 7, 14);
        $data[] = array(110, 8, 15);
        $data[] = array(110, 11, 1);
        $data[] = array(110, 11, 2);
        $data[] = array(110, 11, 11);

        //Société 120
        $data[] = array(120, 1, 1);
        $data[] = array(120, 12, 25);
        $data[] = array(120, 5, 1);
        $data[] = array(120, 5, 8);
        $data[] = array(120, 7, 14);
        $data[] = array(120, 8, 15);
        $data[] = array(120, 11, 1);
        $data[] = array(120, 11, 2);
        $data[] = array(120, 11, 11);

        //Société 130
        $data[] = array(130, 1, 1);
        $data[] = array(130, 12, 25);
        $data[] = array(130, 5, 1);
        $data[] = array(130, 5, 8);
        $data[] = array(130, 7, 14);
        $data[] = array(130, 8, 15);
        $data[] = array(130, 11, 1);
        $data[] = array(130, 11, 2);
        $data[] = array(130, 11, 11);

        //Société 222
        $data[] = array(222, 1, 1);
        $data[] = array(222, 12, 25);
        $data[] = array(222, 5, 1);
        $data[] = array(222, 5, 8);
        $data[] = array(222, 7, 14);
        $data[] = array(222, 8, 15);
        $data[] = array(222, 11, 1);
        $data[] = array(222, 11, 2);
        $data[] = array(222, 11, 11);

        //Société 240
        $data[] = array(240, 1, 1);
        $data[] = array(240, 12, 25);
        $data[] = array(240, 5, 1);
        $data[] = array(240, 5, 8);
        $data[] = array(240, 7, 14);
        $data[] = array(240, 8, 15);
        $data[] = array(240, 11, 1);
        $data[] = array(240, 11, 2);
        $data[] = array(240, 11, 11);

        //Société 301
        $data[] = array(301, 1, 1);
        $data[] = array(301, 12, 25);
        $data[] = array(301, 5, 1);
        $data[] = array(301, 5, 8);
        $data[] = array(301, 7, 14);
        $data[] = array(301, 8, 15);
        $data[] = array(301, 11, 1);
        $data[] = array(301, 11, 2);
        $data[] = array(301, 11, 11);

        return $data;
    }

}