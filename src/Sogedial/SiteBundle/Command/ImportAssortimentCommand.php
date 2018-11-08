<?php
// 301-484259
namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Assortiment;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportAssortimentCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importAssortimentCsv',
            'Import assortiment from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'assortiments';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function import($data, OutputInterface $output)
    {

        $i = 1;
        $skipped = 0;
        $added = 0;
        $now = new \DateTime();
        
        foreach ($data as $row) {            
            // filtrage par société - voir aussi le cas "aucun assortiment" ci-dessous ; le code à modifier si on enlève le filtrage (et passe en solution BDD unique)
            /*if ($row[2] !== $societe_site)
            {
                $this->issue($i, $output, "Inconsistent composite field.");
            }
            else
            {*/
            $added++;
            $valeur = (strlen($row[5]) === 1) ? "00" . $row[5] : ((strlen($row[5]) === 2) ? "0" . $row[5] : $row[5]);
            $ass_code = utf8_encode($row[2].'-'.$valeur.$row[1]);                                    // contournement de fautes de frappe
            if (utf8_encode($row[2].'-'.$row[0]) !== $ass_code)
            {
               // $this->issue($i, $output, "Inconsistent composite field.");
            }

            $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')
                ->findOneBy(array('code' => $ass_code));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => substr($row[2], 0, 1)));

            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $row[2].'-'.utf8_encode($row[1])));              // code région + code entreprise numérique à 2 chiffres + '-' + code produit
                // contournement de fautes de frappe, bis

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[2]));

            if (!($assortiment instanceof Assortiment)) {
                $assortiment = new Assortiment();
                $assortiment->setCode($ass_code);
            }

            if ($produit instanceof Produit) {
                $assortiment->setProduit($produit);
            }

            if ($region instanceof Region) {
                $assortiment->setRegion($region);
            }

            if ($entreprise instanceof Entreprise) {
                $assortiment->setEntreprise($entreprise);
            }

            $assortiment->setValeur($valeur);

            $assortiment->setUpdatedAt($now);
            parent::$em->merge($assortiment);
            parent::advance($i, $output);
            $i++;
            //}
        }

        $this->finish();
        $output->writeln($skipped.' "assortiment" entries skipped.' );

        // attention : ce code ne fonctionnera plus si on enlève le filtrage par société, car on peut avoir le total différent de zéro, mais
        // zéro assortiment pour certaines sociétés !

        if ($added === 0)        // aucun assortiment ajouté - cela peut aussi arriver en cas de CSV multi-société avec zéro assortiments pour la société courante
        {
            //TODO revenir sur ce test ça ne marche pas
            // if (parent::$ms->hasFeature('ingestion-ajouter-assortiment-complet-si-pas-d-assortiments'))
            if($this->getRegionNumeric() == 3)
            {

                //TODO remplacer ça : car ici quick fixe pour sofrigu
                //$societe_site = $this->getSocieteSite();
                $societe_site = 301;

                $sql = $this->getContainer()->get('sogedial.mysql');
                $all_product_codes = $sql->query("SELECT DISTINCT p.code_produit FROM produit p INNER JOIN degressif d ON p.code_produit = d.code_produit WHERE p.code_produit LIKE '".$societe_site."-%';");

                $i = 1;
                $fullAssortiment = "777";
                $progress = new ProgressBar($output, count($all_product_codes));
                $progress->start();

                foreach($all_product_codes as $code_produit_enrichi_row)
                {
                    $code_produit_enrichi = $code_produit_enrichi_row['code_produit'];

                    $code_produit_origine = substr($code_produit_enrichi,4);                // "222-CODEPRODUIT" -> "CODEPRODUIT"
                    $ass_code = $societe_site.'-'.$fullAssortiment.$code_produit_origine;

                    $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')
                      ->findOneBy(array('code' => $ass_code));

                    $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                        ->findOneBy(array('code' => $code_produit_enrichi));              // code région + code entreprise numérique à 2 chiffres + '-' + code produit

                    // si on sort $region et $entreprise de la boucle, Doctrine n'est pas content et demande de configurer cascade = "persist"

                    $region = parent::$em->getRepository('SogedialSiteBundle:Region')->findOneBy(array('code' => $this->getRegionNumeric()));

                    $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array(   'code' => $societe_site   ));

                    if (   (!($region instanceof Region)) || (!($entreprise instanceof Entreprise))    )
                    {
                        $this->issue($i, $output, "ERROR : Missing 'region' or 'entreprise'.");
                    }

                    if (!($assortiment instanceof Assortiment)) {
                        $assortiment = new Assortiment();
                        $assortiment->setCode($ass_code);
                    }

                    if ($produit instanceof Produit)
                    {
                        $assortiment->setProduit($produit);
                    }
                    else
                    {
                        $this->issue($i, $output, "ERROR : can not find product.");
                    }

                    if ($region instanceof Region)
                    {
                        $assortiment->setRegion($region);
                    }
                    if ($entreprise instanceof Entreprise)
                    {
                        $assortiment->setEntreprise($entreprise);
                    }

                    $assortiment->setValeur($fullAssortiment);
                    $assortiment->setUpdatedAt($now);
                    
                    parent::$em->persist($assortiment);
                    parent::advance($i, $output);
                    $i++;
                }


                $this->finish();
                $output->writeln('Custom feature: Added a complete "assortiment".');
            }
        }
        //TODO fonction recativée par @Sictoz
        $this->cleanOldAssortiment($now);

        return $skipped;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        //TODO ce test test ne fonctionne pas
        //if (parent::$ms->hasFeature('ingestion-ajouter-assortiment-complet-si-pas-d-assortiments')) {
        //TODO Revenir sur ce quick fixe ici on suppose que region 3 il y a que sofrigu
        if($this->getRegionNumeric() == 3)
        {
            return array();
        }

        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMASSOP.CSV', ',');

        if ($data !== false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/ASSORTIM.CSV', ',');

        return $data;
    }

    /**
     * @return bool
     */
    protected function truncateAssortiment()
    {
        parent::$em = $this->getContainer()->get('doctrine')->getManager();
        parent::$em->getConnection()->query('START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; TRUNCATE assortiment; SET FOREIGN_KEY_CHECKS=1; COMMIT;');

        return true;
    }

    /**
     * @param $dateImport
     * @return bool
     */
    protected function cleanOldAssortiment($dateImport)
    {
        parent::$em = $this->getContainer()->get('doctrine')->getManager();
        parent::$em->getConnection()->query(
            "
                START TRANSACTION;SET FOREIGN_KEY_CHECKS=0; DELETE from assortiment where (updated_at < '".$dateImport->format('Y-m-d H:i:s')."' or updated_at IS NULL ) and code_assortiment NOT LIKE '%PRP%' and code_region = ".$this->getRegionNumeric()." ; SET FOREIGN_KEY_CHECKS=1; COMMIT;
             "
        );
        return true;
    }
}