<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\ProduitMeti;
use Sogedial\SiteBundle\Entity\ClientMeti;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Validator\Constraints\DateTime;

class ImportClientMetiCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importClientMetiCsv',
            'Import client meti from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'clientsMeti';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $now_date = new \DateTime('now');
        $skipped = 0;
        $critical = 0;
        $currentRegion = $this->getRegionNumeric();
        var_dump($currentRegion);

        $region = parent::$em->getRepository('SogedialSiteBundle:Region')
        ->findOneBy(array('code' => $currentRegion));

        foreach ($data as $key => $row) {

            if(substr($row[0],0,1) === "#"){ //Ce test etait fait exprès !
                $i++;
                //ligne titre
                continue;
            }


            if(!isset($row[0]) || !isset($row[1]) || !isset($row[6])){
                $skipped++;
                $this->issue($i, $output, "Invalid value for component of code client. ".$row[0]);
                //si pas de code magasin meti ou s'il manque le code region pour le code composite ou le code as400 => skip
                continue;
            }

            $clientMeti = parent::$em->getRepository('SogedialSiteBundle:ClientMeti')
                ->findOneBy(array('code' => $row[0].'-'.$row[1]));

            if (!($clientMeti instanceof ClientMeti)) {
                $clientMeti = new ClientMeti();
                $clientMeti->setCode($row[0].'-'.$row[1]);
                $clientMeti->setCreatedAt($now_date);
            }

            $regionClientMeti = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[0]));
            
            if (!($regionClientMeti instanceof Region)) {
                $skipped++;
                $this->issue($i, $output, "Region not found or invalid.");
                continue;
            }

            if($regionClientMeti->getCode() !== $region->getCode()){
                $skipped++;
                $this->issue($i, $output, "Not current region => skipped.");
                continue;
            }

            $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')->findOneBy(array('code' => $row[0]."-".$row[4]));
            if (!($enseigne instanceof Enseigne)) {
                $skipped++;
                $this->issue($i, $output, "Enseigne not found or invalid.");
                continue;
            }
            $clients = parent::$em->getRepository('SogedialSiteBundle:ClientMeti')->getClientObjectsFromCodeClient($row[6]);

            for ($i=0; $i<count($clients); $i++) {
                if($clients[$i] instanceof Client && $row[6] !== "") {
                    $clients[$i]->setIsClientMeti(true);
                    parent::$em->persist($clients[$i]);
                }
            }

            $clientAs400 = $row[6];


            //Entity binding
            $clientMeti->setCodeMeti($row[1]);
            $clientMeti->setLibelleSite($row[2]);
            $clientMeti->setRegion($regionClientMeti);
            $isSafo = ($row[3] === "O");
            $clientMeti->setIsSafo($isSafo);
            $clientMeti->setEnseigne($enseigne);
            $clientMeti->setLibelleEnseigne($row[5]);
            $clientMeti->setClientAs400($clientAs400);
            $clientMeti->setUpdatedAt($now_date);

            //TODO: vérifier que le mail est bien dans la bonne colonne
            //Ici j'ai mis la colonne 10 de maniere arbitraire
            // if (sizeof($row) >= 10 && isset($row[10])){
            //     if (preg_match('#^[\w.-]+@[\w.-]+\.[a-z]{2,6}$#i', trim($row[10]))) {
            //         $clientMeti->setMailReferencement(trim($row[10]));
            //     }
            // }

            parent::$em->persist($clientMeti);
            parent::advance($i, $output);
            $i++;
        }

        
        $this->finish();
        $output->writeln('');
        $output->writeln(($critical ? $critical : "No").' critical errors in "clientMeti" entries.' );
        return $skipped;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/CLIENT-METI.CSV', ',');

        return $data;
    }
}