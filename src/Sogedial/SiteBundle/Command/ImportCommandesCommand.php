<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Command\ImporterCommands;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\BonPreparation;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Sogedial\SiteBundle\Entity\OrderStatus;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCommandesCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importCommandesCommandCsv',
            'Import commands from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'commandes';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    private function labelStatusToIdStatus($labelStatus){
        switch($labelStatus){
            case 'E': return 3; // commande validee
            case 'F': return 9; // commande facturee
            case 'S': return 5; // commande supprimee
            case 'A': return 8; // commande rejetee
            default : return -1; //inconnu
        }
    }

    /**
    * @return integer count of lines skipped
    */
    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $skipped = 0;
        foreach ($data as $row) {
            $codeEntreprise = $row[0];
            /*if ($codeEntreprise !== $this->societe_site) {
                $skipped++;
                continue;
            }*/

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('valeur' => $codeEntreprise));
            if($entreprise === NULL){
                echo "l'entreprise " . $codeEntreprise . " n'existe pas\n";
                continue;
            }
            $client = parent::$em->getRepository('SogedialSiteBundle:Client')->findOneBy(array('code' => ($entreprise->getCode()). '-' .$row[8]));
            if($client === NULL){
                echo "le client " . $entreprise->getCode() . "-" . $row[8] . " n'existe pas\n";
                continue;
            }

            $numeroCommande = sprintf("%'.09d", $row[4]);

            $status = $this->labelStatusToIdStatus($row[2]);
            if($status === -1){
                echo "le status " . $row[2] . " n'existe pas\n";
                continue;
            } else if($status !== 9){ // status != F
                echo "La commande n'est pas facturé\n";
                continue;
            }
            $command = parent::$em->getRepository('SogedialSiteBundle:Commande')->findOneBy(
                array(
                    'numero' => $numeroCommande,
                    'entreprise' => $codeEntreprise,
                    'applicationOrigine' => $row[1]
                )
            );

            if($command === NULL){
                $command = new Commande();
                $this->initCommand($command, $client, $numeroCommande, $entreprise, $row[1], $row);
                $orderOrderStatus = new OrderOrderStatus();
            } else {
                $orderOrderStatus = parent::$em->getRepository('SogedialSiteBundle:OrderOrderStatus')
                    ->findOneBy(array('order' => $command->getId()));
                if($orderOrderStatus === NULL){
                    $orderOrderStatus = new OrderOrderStatus();
                }
            }

            $this->setCommand($command, $row, $status);
            parent::$em->persist($command);

            //si row[3] different de zero, alors il s'agit d'une facturation, avec un bon de préparation
            if($row[3] !== '0'){
                $bp = parent::$em->getRepository('SogedialSiteBundle:BonPreparation')->findOneBy(
                    ['code' => $row[3]]
                );
                if($bp === NULL){ 
                    $bp = new BonPreparation($command, $row[3]);                                   
                }
                $this->setBP($bp, $row);
            }

            $orderStatus = parent::$em->getRepository('SogedialSiteBundle:OrderStatus')->findOneBy(array('id' => $status));
            // si la commande n'est pas rejetee
            if(!($command === NULL && $row[1] === 'A7' && $row[2] === 'A')){
                $this->setCommandOrderStatus($orderOrderStatus, $orderStatus, $command);
                parent::$em->persist($orderOrderStatus);
            }
            $this->finish();
            $i++;
            parent::advance($i, $output);
        }

        $this->setSkipped($skipped);
        return $skipped;
    }

    /** 
    * @param bp BonPrepation
    * @param row line of CSV
    */
    private function setBP($bp, $row){
        //$bp = new BonPreparation($command, $row[3]);

        $bp->setDeliveryDate(new \DateTime($row[13]));
        $bp->setDateFacturation(($row[14] !== '0') ? new \DateTime($row[14]) : NULL);
        $bp->setNumeroFacturation($row[15]);
        $bp->setMontantFacturation(floatval($row[19]));
        $bp->setColisFacture(intval($row[18]));

        parent::$em->persist($bp);
    }

    private function initCommand($command, &$client, &$numeroCommande, &$entreprise, $applicationOrigine){
        $command->setClient($client);
        $command->setNumero($numeroCommande);
        $command->setEntreprise($entreprise);
        $command->setApplicationOrigine($applicationOrigine);
    }

    private function setCommand(&$command, &$row, &$status){
        /*
        if($row[13] !== '0'){
            $command->setDeliveryDate(new \DateTime($row[13]));
        }
        */
        if($row[16] !== '0'){
            $command->setUpdatedAt($this->setDateTime($row[16], $row[17]));
        }

        // si status = 9, alors la commande a ete facturee
        if($status === 9){
            /*
            if($row[15] !== '0' && $row[15] !== 0 && $row[15] !== NULL){
                //$command->setNumeroFacturation($row[15]);
            }

            $command->setDateFacturation(($row[14] !== '0') ? new \DateTime($row[14]) : NULL);

            if($row[19] !== '0' && $row[19] !== 0 && $row[19] !== NULL){
                //$command->setMontantFacturation(floatval($row[19]));
            }
            */
        } else {
            //si une commande n'a pas de parent, alors elle vient directement de l'AS400
            if($command->getParent() !== NULL){
                $lignesCommande = parent::$em->getRepository('SogedialSiteBundle:LigneCommande')->getLigneByOrderIdAndTemperature($command->getParent(), $command->getTemperatureCommande());
                if($lignesCommande === NULL){
                    $command->setMontantCommande(floatval($row[19]));
                }
            } else {
                $command->setMontantCommande(floatval($row[19]));                
            }
        }

        /*
        if($row[18] !== '0' && $row[18] !== 0 && $row[18] !== NULL){
            $command->setColisFacture(intval($row[18]));
        }
        */
    }

    private function setCommandOrderStatus(&$orderOrderStatus, &$orderStatus, &$command){
        $orderOrderStatus->setOrderStatus($orderStatus);
        $orderOrderStatus->setUpdatedAt(new \DateTime('NOW'));
        $orderOrderStatus->setCreatedAt(new \DateTime('NOW'));
        $orderOrderStatus->setOrder($command);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/SUICDEW-A2.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/SUIVI.CSV', ',');

        return $data;
    }
}