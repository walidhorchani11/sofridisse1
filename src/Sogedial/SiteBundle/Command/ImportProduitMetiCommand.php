<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\ProduitMeti;
use Sogedial\SiteBundle\Entity\ClientMeti;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Validator\Constraints\DateTime;

class ImportProduitMetiCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importProduitMetiCsv',
            'Import produit meti from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'produitsMeti';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
       // $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $now_date = new \DateTime('now');
        $skipped = 0;
        $critical = 0;
        $currentRegion = $this->getRegionNumeric();

        $region = parent::$em->getRepository('SogedialSiteBundle:Region')
        ->findOneBy(array('code' => $currentRegion));

        $codes = [];
        foreach ($data as $row) {

            if(substr($row[0],0,1) === "#"){ //Ce test etait fait exprès !
                $i++;
                //ligne titre
                continue;
            }

            //if(!isset($row[0]) || !isset($row[1]) || !isset($row[2]) || !isset($row[3]) || !isset($row[4])){
            if($row[0] === "" || $row[1] === "" || $row[2] === "" || $row[3] === "" || $row[4] === ""){
               $i++;
               $skipped++;
                $this->issue($i, $output, "Missing field.");
                //si pas de code magasin meti ou s'il manque le code societe pour le code composite => skip
                continue;
            }




            $produitMeti = parent::$em->getRepository('SogedialSiteBundle:ProduitMeti')
                ->findOneBy(array('code' => $row[0].'-'.$row[2].'-'.$row[3]));

            if (!($produitMeti instanceof ProduitMeti)) {
                $produitMeti = new ProduitMeti();
                $produitMeti->setCode($row[0].'-'.$row[2].'-'.$row[3]);
                $produitMeti->setCreatedAt($now_date);
            }



            $regionMeti = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => substr($row[1],0,1)));

            if (!($regionMeti instanceof Region)) {
                $i++;
                $skipped++;
                $this->issue($i, $output, "Region not found or invalid => ".substr($row[1],0,1) );
                continue;
            }

            if($regionMeti->getCode() !== $region->getCode()){
                $i++;
                $skipped++;
                $this->issue($i, $output, "Not current region => skipped.");
                continue;
            }

            $clientMeti = parent::$em->getRepository('SogedialSiteBundle:ClientMeti')
                ->findOneBy(array('codeMeti' => $row[0]));

            if(!($clientMeti instanceof ClientMeti)){
                $i++;
                $skipped++;
                $this->issue($i, $output, "Client meti not found => skipped ". $row[0]);
                continue;
            }

            $clientAs400 = parent::$em->getRepository('SogedialSiteBundle:Client')
                ->findOneBy(array('code' => $row[1].'-'.$clientMeti->getClientAs400()));

            if (!($clientAs400 instanceof Client)) {
                $skipped++;
                $i++;
                $this->issue($i, $output, "Client AS400 not found or invalid => (codeMeti = ".$row[0].") " . $row[1] . "-" . $clientMeti->getClientAs400());
                continue;
            }

            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('ean13' => $row[4], 'entreprise' => $row[1]));
            
            if (!($produit instanceof Produit)) {
                $skipped++;
                $i++;
                $this->issue($i, $output, "Produit equivalent not found or invalid.");
                continue;
            }

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[1]));

            $produitMeti->setClientMeti($clientMeti);
            $produitMeti->setSociete($row[1]);
            $produitMeti->setProduitMeti($row[2]);
            $produitMeti->setEan13($row[4]);
            $produitMeti->setStock(intval(trim($row[5])));

            //Entity binding
            $produitMeti->setRegion($regionMeti);
            $produitMeti->setClient($clientAs400);
            $produitMeti->setProduit($produit);
            $produitMeti->setEntreprise($entreprise);

            $produitMeti->setUpdatedAt($now_date);
            parent::$em->persist($produitMeti);
            parent::advance($i, $output);
            $i++;
        }

        $this->finish();
        $output->writeln('');
        $output->writeln(($critical ? $critical : "No").' critical errors in "produit" entries.' );
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
        if ($this->getRegion()=='region1'){
            $filename = 'preco_1.csv';
        }elseif($this->getRegion()=='region2'){
            $filename = 'preco_2.csv';
        }else{
            $filename = 'PRODUIT-METI.CSV';
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/'.$filename, ',');

        return $data;
    }
}