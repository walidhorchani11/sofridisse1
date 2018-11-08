<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Assortiment;
use Sogedial\SiteBundle\Entity\AssortimentClient;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Tarif;
use Sogedial\SiteBundle\Entity\Tarification;
use Sogedial\SiteBundle\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportClientCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importClientCsv',
            'Import client from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'clients';
        $this->executeCmd($this, $commandeName, $input, $output);        
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    private function setAssortimentClient($data, OutputInterface $output, $currentRegion)
    {
        $backupAssortimentComplet = parent::$ms->hasFeature('ingestion-ajouter-assortiment-complet-si-pas-d-assortiments');
        $i = 0;
        $this->setLabelExecution("ClientAssortiment");
        $this->initProgress(count($data));
        $this->displayStartHeader();
        foreach ($data as $row) {
            $valeur = $row[2];

            $client = parent::$em->getRepository('SogedialSiteBundle:Client')
                ->findOneBy(array('code' => $row[4].$row[5].'-'.$row[0]));
    
            $assortimentClient = parent::$em->getRepository('SogedialSiteBundle:AssortimentClient')
                ->findOneBy(array('nom' => 'Catalogue', "client" => $client));
    
            $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')     // INCOHERENCE structure de BDD : on accroche une entrée aléatoire de la table "assortiment"
                ->findOneBy(array('valeur' => $row[2], 'entreprise' => $row[4].$row[5]));

            if(!$assortimentClient){
                $assortimentClient = new AssortimentClient();
            }

            if ($currentRegion === '3'){
                $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')->findOneBy(array('valeur' => '777'));       // INCOHERENCE structure de BDD : on accroche une entrée aléatoire de la table "assortiment"
                if ($assortiment instanceof Assortiment) {
                    $assortimentClient->setAssortiment($assortiment);
                } else {
                    $this->issue($i, $output, "Fallback 'assortiment complet' '".$row[4].$row[5].'-'."777' not found (internal problem possible!)");
                }
                $assortimentClient->setValeur(777);
            } else {
                $assortimentClient->setAssortiment($assortiment);
                $assortimentClient->setValeur($valeur);
            }

            $assortimentClient->setNom('Catalogue');
            $assortimentClient->setAs400assortiment(true);
            $assortimentClient->setAssortimentCourant(true);
            $assortimentClient->setClient($client);
            parent::$em->persist($assortimentClient);


            parent::advance($i, $output);
            $i++;
        }
        $this->finish();
        $this->displayFinishHeader();
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 1;
        $skipped = 0;
        $societe_site = $this->getSocieteSite();
        $backupAssortimentComplet = parent::$ms->hasFeature('ingestion-ajouter-assortiment-complet-si-pas-d-assortiments');
        $currentRegion = $this->getRegionNumeric();

        foreach ($data as $row) {

            $client = parent::$em->getRepository('SogedialSiteBundle:Client')
                    ->findOneBy(array('code' => $row[4].$row[5].'-'.$row[0]));

            $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')
                ->findOneBy(array('code' => $currentRegion.'-'.$row[1]));

            $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')     // INCOHERENCE structure de BDD : on accroche une entrée aléatoire de la table "assortiment"
                ->findOneBy(array('valeur' => $row[2], 'entreprise' => $row[4].$row[5]));

            $tarification = parent::$em->getRepository('SogedialSiteBundle:Tarification')
                ->findOneBy(array('code' => $row[3]));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[4]));

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $row[4].$row[5]));

            if (!($client instanceof Client)) {
                $client = new Client();
                $client->setCode($row[4].$row[5].'-'.$row[0]);
            }

            if ($enseigne instanceof Enseigne) {
                $client->setEnseigne($enseigne);
            }
            else
            {
                $enseigne = parent::$em->getRepository('SogedialSiteBundle:Enseigne')->findOneBy(array('code' => $currentRegion.'-'."SE$"));
                if ($enseigne instanceof Enseigne) {
                    $client->setEnseigne($enseigne);
                }
                else
                {
                    $this->issue($i, $output, "Fallback 'enseigne' 'SE$' not found (internal problem possible!)");
                }
            }

            if ($tarification instanceof Tarification) {
                $client->setTarification($tarification);
            }
            else if($row[3] !== "")
            {
                $tarification = new Tarification;
                $tarification->setCode($row[3]);
                parent::$em->persist($tarification);
                parent::$em->flush();

                $client->setTarification($tarification);
            }

            if ($assortiment instanceof Assortiment) {
                $client->setAssortiment($assortiment);
            }
            else if ($backupAssortimentComplet)
            {
                $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')->findOneBy(array('valeur' => '777'));       // INCOHERENCE structure de BDD : on accroche une entrée aléatoire de la table "assortiment"
                if ($assortiment instanceof Assortiment) {
                    $client->setAssortiment($assortiment);
                }
                else
                {
                    $this->issue($i, $output, "Fallback 'assortiment complet' '".$row[4].$row[5].'-'."777' not found (internal problem possible!)");
                }
            }

            if ($region instanceof Region) {
                $client->setRegion($region);
            }

            if ($entreprise instanceof Entreprise) {
                $client->setEntreprise($entreprise);
            }

            /*
            if ($assortiment instanceof Assortiment) {
                $client->setAssortiment($assortiment);
            }
            else if ($backupAssortimentComplet)
            {
                $assortiment = parent::$em->getRepository('SogedialSiteBundle:Assortiment')->findOneBy(array('valeur' => '777'));       // INCOHERENCE structure de BDD : on accroche une entrée aléatoire de la table "assortiment"
                if ($assortiment instanceof Assortiment) {
                    $client->setAssortiment($assortiment);
                }
                else
                {
                    $this->issue($i, $output, "Fallback 'assortiment complet' '".$row[4].$row[5].'-'."777' not found (internal problem possible!)");
                }
            }
            */

            $client->setCompteurPromotions(0);
            $client->setNom(utf8_encode($row[6]));
            if($row[7] === 'NULL' || $row[7] === NULL){
                $client->setDateDebutValidite(new \DateTime('now'));
            } else {
                $client->setDateDebutValidite(new \DateTime($row[7]));
            }
            $client->setAdresse1(utf8_encode($row[8]));
            $client->setCodePostale($row[9]);
            $client->setVille($row[10]);
            $client->setTelephone($row[11]);
            $client->setFax($row[12]);
            $client->setEmail($row[13]);
            $client->setStatut($row[14]);
            $client->setResponsable1(utf8_encode($row[15]));
            $client->setResponsable2(utf8_encode($row[16]));
            $client->setAdresse2(utf8_encode($row[17]));
            $client->setRegroupementClient($row[18]);
            parent::$em->persist($client);
            parent::advance($i, $output);
            $i++;
        //}
        }

        $this->finish();
        $output->writeln($skipped.' "client" entries skipped.' );        

        $this->setAssortimentClient($data, $output, $currentRegion);

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
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMCLIEP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/CLIENT.CSV', ',');

        return $data;
    }
}