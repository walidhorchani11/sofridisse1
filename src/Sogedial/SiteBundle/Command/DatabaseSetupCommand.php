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


class DatabaseSetupCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:executeDatabaseSetup', 
            'Run necessary commands to update DB from csv files'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface **/
        $logger = $this->getContainer()->get('logger');

        $output->writeln('Running database update setup ... ');
        $logger->info('Running database update setup ...');

        $this->runRegion($input, $output, $input->getArgument('regions'));

        $logger->info('Database update complete');
        $output->writeln('Database update complete');
    }

    private function runRegion(InputInterface $input, OutputInterface $output, $regions){
        $commands = [
            $this->getApplication()->find('sogedial:setupInternalTables'),
            $this->getApplication()->find('sogedial:importDepartementCsv'),
            $this->getApplication()->find('sogedial:importSecteurCsv'),
            $this->getApplication()->find('sogedial:importMarqueCsv'),
            $this->getApplication()->find('sogedial:importEnseigneCsv'),
            $this->getApplication()->find('sogedial:importRayonCsv'),
            $this->getApplication()->find('sogedial:importFamilleCsv'),
            $this->getApplication()->find('sogedial:importSousFamilleCsv'),
            $this->getApplication()->find('sogedial:importSegmentCsv'),
            $this->getApplication()->find('sogedial:importProduitCsv'),
            $this->getApplication()->find('sogedial:importColisCsv'),
            $this->getApplication()->find('sogedial:importDegressifCsv'),
            $this->getApplication()->find('sogedial:importAssortimentCsv'),
            $this->getApplication()->find('sogedial:importClientCsv'),
            $this->getApplication()->find('sogedial:importSupplierCsv'),
            $this->getApplication()->find('sogedial:importPromotionCsv'),
            $this->getApplication()->find('sogedial:importStockCsv'),
            $this->getApplication()->find('sogedial:importCommandesCommand'),
            //$this->getApplication()->find('sogedial:importMOQCsv'),
            $this->getApplication()->find('sogedial:importTarifCsvFirstSheet'),
       ];
        $commandsLen = count($commands);

        $activeDefault = false;
        if(count($regions) === 0){
            $activeDefault = true;
            $regions = $this->getRegionAll();
        }
        $regionsLen = count($regions);

        $regionSkipped = 0;

        foreach($regions as $region => $regionId){
            $commandsSkipped = 0;
            foreach($commands as $command){
                $command->setRegion($region);
                $command->setRegionNumeric($regionId);
                $command->run($input, $output);
                if($command->getSkipped() === -1){
                    $commandsSkipped++;
                }
            }
            if($commandsLen === $commandsSkipped){
                $regionSkipped++;
            }
        }
    }
}