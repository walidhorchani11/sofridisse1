<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Psr\Log\LoggerInterface;


class DatabaseStockUpdateCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:executeStockUpdate',
            'Import enseigne from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');
        $commandeName1 = 'stocks';
        $this->executeCmd($this, $commandeName1, $input, $output);

        $commandeName2 = 'promotions';
        $this->executeCmd($this, $commandeName2, $input, $output);

        $output->writeln('Running database update setup ...');
        $logger->info('Running database update setup ...');
        // $command1 = $this->getApplication()->find('sogedial:importStockCsv');
        // $command2 = $this->getApplication()->find('sogedial:importPromotionCsv');

        // $command1->run($input, $output);
        // $command2->run($input, $output);
        $logger->info('MAJ de la base de donnée terminée');
        $output->writeln('MAJ de la base de donnée terminée');
    }

        /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            $this->getApplication()->find('sogedial:importPromotionCsv'),
            $this->getApplication()->find('sogedial:importStockCsv')
        ];
        $commandsLen = count($commands);

        foreach($commands as $command){
            $command->run($input, $output);
        }
        return array();
    }

    protected function import($data, OutputInterface $output)
    {
    }
}