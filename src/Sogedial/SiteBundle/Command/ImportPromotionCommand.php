<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Marque;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Promotion;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportPromotionCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importPromotionCsv',
            'Import promotion from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'promotions';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $societe_site = $this->getSocieteSite();
        $i = 1;
        $skipped = 0;
        $dateImport = new \DateTime('NOW');
        $currentRegion = $this->getRegionNumeric();

        foreach ($data as $row) {
            $societeNum = parent::$ms->getSocieteNumByAlpha($row[3]);

            if (trim($row[0]) !== "" && trim($row[2]) !== "")
            {
                $this->issue($i, $output, "'Enseigne' and 'client' are present at the same time.");
            }
            if (trim($row[0]) === "" && trim($row[2]) === "")
            {
                $this->issue($i, $output, "'Enseigne' and 'client' are absent at the same time.");
            }

            $codeTypePromo = trim($row[14]);
            $traitementException = trim($row[9]);

            // les traitements d'exception obtiennent le code promo "TX" pour un traitement plus uniforme en aval
            if ($traitementException === "O") {
                if ($codeTypePromo === "") {
                    $codeTypePromo = "TX";
                } else {
                    $this->issue($i, $output, "'code_type_promo' is provided for an exceptional special offer, but it should be empty.");
                }
                if (trim($row[0]) === "") {
                    $this->issue($i, $output, "'Enseigne' is not provided for an exceptional special offer.");
                }
            } elseif ($traitementException !== "") {
                $this->issue($i, $output, "Unknown exceptional special offer code : '".$traitementException."'");
            }

            // si le type promo n'est pas fourni (ex. : Sofrigu), on essaie de le déterminer automatiquement
            if ($codeTypePromo === "") {
                if (trim($row[0]) === "" && trim($row[2]) !== "") {
                    $codeTypePromo = "CL";                              // client
                } else if (trim($row[0]) !== "" && trim($row[2]) === "") {
                    $codeTypePromo = "EN";                              // enseigne
                } else {
                    $codeTypePromo = "XX";                              // inconnu
                    $this->issue($i, $output, "Special offer type ('codeTypePromo') is absent and could not be determined automatically.");
                }
            } elseif (!in_array($codeTypePromo,  array('EF','MA','CE','CL','RG','EN','CA', 'FR', 'TX')  )) {
                $this->issue($i, $output, "Unknown special offer type ('codeTypePromo') : '".$codeTypePromo."'");
            }

            //code_enseigne -> $row[0] / code_client -> $row[2] / code_category_client -> $row[13] / regroupement_client -> $row[12]
            //Creation de l'attribut manquant pour l'unicité du code promotion
            if(trim($row[0]) !== ""){
                $lstmyu = trim($row[0]); 
            } 
            else if(trim($row[2]) !== ""){
                $lstmyu = trim($row[2]); 
            }
            else if(trim($row[13]) !== ""){
                $lstmyu = trim($row[13]); 
            }
            else if(trim($row[12]) !== ""){
                $lstmyu = trim($row[12]); 
            }
            else{
                $skipped++;
                $this->issue($i, $output, "It's missing \$lstmyu !");
                continue;
            }
            
            if( $societeNum !== 'XXX' && trim($row[5])!== '' &&  $row[7] != 0){
                $promotion = parent::$em->getRepository('SogedialSiteBundle:Promotion')
                    ->findOneBy(array('code' => $societeNum.'-'.$row[5].'-'.$codeTypePromo.'-'.$lstmyu.'-'.$row[7]));
            } else {
                $skipped++;
                $this->issue($i, $output, "Unknown society : '".$row[3]."'");
                continue;
            }

            if ($row[7] != 0 && $row[8] != 0) {
                $startDate = new \DateTime($row[7]);
                if($row[8] === '99999999'){
                    $endDate = new \DateTime('99991231');
                }
                else{
                    $endDate = new \DateTime($row[8]);
                }
            }
            else{
                $skipped++;
                $this->issue($i, $output, "Invalid start or end date");
                continue;
            }

            if (!($promotion instanceof Promotion)) {
                $promotion = new Promotion();
                $promotion->setCode($societeNum.'-'.$row[5].'-'.$codeTypePromo.'-'.$lstmyu.'-'.$row[7]);
            }

            $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')
                ->findOneBy(array('code' => $currentRegion.'-'.trim($row[0])));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[1]));

            $client = parent::$em->getRepository('SogedialSiteBundle:Client')
                ->findOneBy(array('code' => $societeNum .'-'.trim($row[2])));

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $societeNum));

            $supplier = parent::$em->getRepository('SogedialSiteBundle:Supplier')
                ->findOneBy(array('code' => $societeNum .'-'.$row[4]));

            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $societeNum .'-'. $row[5]));


            if ($enseigne instanceof Enseigne) {
                $promotion->setEnseigne($enseigne);
            }

            if ($region instanceof Region) {
                $promotion->setRegion($region);
            }

            if ($client instanceof Client) {
                $promotion->setClient($client);
            }

            if ($entreprise instanceof Entreprise) {
                $promotion->setEntreprise($entreprise);
            }

            if ($supplier instanceof Supplier) {
                $promotion->setSupplier($supplier);
            }

            if ($produit instanceof Produit) {
                $promotion->setProduit($produit);
            }

            $promotion->setDateDebutValidite($startDate);
            $promotion->setDateFinValidite($endDate);
            $promotion->setCreatedAt(new \DateTime('NOW'));

            $promotion->setUpdatedAt($dateImport);
            $promotion->setCodeTraitementException($traitementException);
            $promotion->setReventePerte($row[10]);
            $promotion->setPrixHt(str_replace(',', '.', $row[11]));
            $promotion->setRegroupementClient($row[12]);
            $promotion->setCodeCategoryClient($row[13]);
            $promotion->setCodeTypePromo($codeTypePromo);
            $promotion->setStockEngagement($row[15]);
            $promotion->setStockEngagementRestant($row[16]);
            $promotion->setCommandeFacture($row[17]);
            //$promotion->setCommandeEnCours($row[18]);
            $promotion->setCommandeEnCours(0);                // il faut toujours mettre zéro car l'information dans row[18] n'est pas fiable

            parent::$em->persist($promotion);
            parent::advance($i, $output);
            $i++;
        }
        $this->finish();
        $output->writeln('');
        //$output->writeln($skipped.' "promotion" entries skipped.' );        
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
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMPROMP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PROMO.CSV', ',');

        $this->deletePromotionByRegion($this->getRegion());

        return $data;
    }

    /**
     * @return bool
     */
    // protected function truncatePromotion()
    // {
    //     parent::$em = $this->getContainer()->get('doctrine')->getManager();
    //     parent::$em->getConnection()->query('START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; TRUNCATE promotion; SET FOREIGN_KEY_CHECKS=1; COMMIT;');

    //     return true;
    // }

    /**
     * @return bool
     */
    protected function deletePromotionByRegion($region)
    {
        parent::$em = $this->getContainer()->get('doctrine')->getManager();
        parent::$em->getConnection()->query('START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; DELETE FROM promotion WHERE code_region = '.$region.' AND created_at < updated_at; SET FOREIGN_KEY_CHECKS=1; COMMIT;');

        return true;
    }

}
