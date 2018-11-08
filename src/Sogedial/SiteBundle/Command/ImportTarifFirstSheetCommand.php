<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Tarif;
use Sogedial\SiteBundle\Entity\Tarification;
use Sogedial\SiteBundle\Entity\Entreprise;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;


class ImportTarifFirstSheetCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importTarifCsvFirstSheet',
            'Import tarif from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'tarifs';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
//        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 1;
        $skipped = 0;
        $societe_site = $this->getSocieteSite();
        $currentRegion = $this->getRegionNumeric();
        if($currentRegion == 4){
            $this->createTarifRegionQuatre();
        }
        else{
            foreach ($data as $row) {
                if ($row[0] !== "" && $row[0] !== $row[2] . $row[3] . $row[4]) {
                    $this->issue($i, $output, "Inconsistent composite field.");
                }

                if (trim($row[5]) == "" || floatval(str_replace(',', '.', trim($row[5]))) <= 0.0) {
                    $this->issue($i, $output, "Price HT is null, 0.0 or negative.");
                    $skipped++;
                    continue;
                }

                $expectedTrigram = parent::$ms->getTrigramBySociete($row[1]);
                if ($row[2] !== "" && $expectedTrigram !== $row[2]) {
                    $this->issue($i, $output, "Bad or Invalide trigram => " . $row[2]);
                    if ($expectedTrigram === "XXX") {
                        $skipped++;
                        continue;
                    }
                }


                $codeTarif = parent::$em->getRepository('SogedialSiteBundle:Tarif')
                    ->findOneBy(array('code' => $row[1] . '-' . $row[3] . '-' . $row[4]));

                $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                    ->findOneBy(array('code' => substr($row[1], 0, 1)));

                $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                    ->findOneBy(array('code' => $row[1]));

                $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                    ->findOneBy(array('code' => $row[1] . '-' . $row[3]));

                //Ici on se base sur la colonne 4 pour l'enseigne et la tarification car on ne sait pas les differencier
                //L'un ou l'autre ne sera pas utiliser selon le multisite service donc ça ne pose pas de probleme meme si les 
                // deux ont la même valeur
                $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')
                    ->findOneBy(array('code' => $currentRegion . '-' . $row[4]));

                if ($currentRegion === "4") {
                    $tarification = NULL;
                } else {
                    $tarification = parent::$em->getRepository('SogedialSiteBundle:Tarification')
                        ->findOneBy(array('code' => $row[4]));
                }

                if ($codeTarif instanceof Tarif) {
                    if ($region instanceof Region) {
                        $codeTarif->setRegion($region);
                    }

                    if ($entreprise instanceof Entreprise) {
                        $codeTarif->setEntreprise($entreprise);
                    }

                    if ($produit instanceof Produit) {
                        if ($produit->getEan13() !== $row[9]) {
                            $this->issue($i, $output, "Inconsistent ean13 field.");
                        }
                        $codeTarif->setProduit($produit);
                    }

                    if ($enseigne instanceof Enseigne) {
                        $codeTarif->setEnseigne($enseigne);
                    }

                    if ($tarification instanceof Tarification) {
                        $codeTarif->setTarification($tarification);
                    }

                    $codeTarif->setPrixHt(str_replace(',', '.', trim($row[5])));
                    $codeTarif->setPrixPvc(str_replace(',', '.', trim($row[6])));
                    if ($row[8] != '0' && $row[8] != '') {
                        $codeTarif->setDateDebutValidite(new \DateTime($row[8]));
                    }

                    parent::$em->persist($codeTarif);
                } else {
                    $tarif = new Tarif();
                    $tarif->setCode($row[1] . '-' . $row[3] . '-' . $row[4]);       // ne pas ingérer $row[0] !! il peut être vide

                    if ($region instanceof Region) {
                        $tarif->setRegion($region);
                    }

                    if ($entreprise instanceof Entreprise) {
                        $tarif->setEntreprise($entreprise);
                    }

                    if ($produit instanceof Produit) {
                        if ($produit->getEan13() !== $row[9]) {
    //                        $this->issue($i, $output, "Inconsistent ean13 field.");
                        }
                        $tarif->setProduit($produit);
                    }

                    if ($enseigne instanceof Enseigne) {
                        $tarif->setEnseigne($enseigne);
                    }

                    if ($tarification instanceof Tarification) {
                        $tarif->setTarification($tarification);
                    }

                    $tarif->setPrixHt(str_replace(',', '.', trim($row[5])));
                    $tarif->setPrixPvc(str_replace(',', '.', trim($row[6])));

                    if ($row[8] != '0' && $row[8] != '') {
                        $tarif->setDateDebutValidite(new \DateTime($row[8]));
                    }

                    parent::$em->persist($tarif);
                }
                parent::advance($i, $output);
                $i++;

            }

            $this->finish();
            $output->writeln($skipped . ' "tarif" entries skipped.');
        }
        return $skipped;
    }

    /**
     * @return bool
    */
     protected function createTarifRegionQuatre()
     {
         parent::$em = $this->getContainer()->get('doctrine')->getManager();

         //Nettoyage des tarifs de la region 4 qui pourrait parasiter
         parent::$em->getConnection()->query('START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; delete FROM tarif WHERE code_tarif like "401-%" and code_region = "4";COMMIT;');
         
         parent::$em->getConnection()->query('START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; insert into tarif(code_tarif, code_enseigne, code_tarification, code_region, code_entreprise, code_produit, prix_ht, prix_pvc, created_at, date_debut_validite, code_ean_13)        
            SELECT CONCAT(p.code_entreprise,"-", SUBSTRING_INDEX(SUBSTRING_INDEX(p.code_produit, "-", -1), " ", 1),"-", SUBSTRING_INDEX(SUBSTRING_INDEX(e.code_enseigne, "-", -1), " ", 1) ) as code_tarif,
            e.code_enseigne as code_enseigne,
            null as code_tarification,
            "4" as code_region,
            "401" as code_entreprise,
            p.code_produit as code_produit,
            p.prix_preste as prix_ht,
            0 as prix_pvc, 
            NOW() as created_at,
            NOW() as date_debut_validite,
            p.ean13_produit as code_ean_13
            FROM produit p, enseigne e 
            WHERE p.code_entreprise = "401" 
            AND e.code_enseigne LIKE "4-%"
            AND e.code_enseigne !=  "4-SE$"
            AND p.prix_preste IS NOT NULL
            AND p.prix_preste > 0; COMMIT;');
         return true;
     }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMTARIP.CSV', ',');

        if ($data !== false && $data !== null) {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/TARIF.CSV', ',');

        return $data;
    }
}