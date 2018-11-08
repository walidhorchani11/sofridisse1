<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Supplier;
use Sogedial\SiteBundle\Entity\Rayon;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Segment;
use Sogedial\SiteBundle\Entity\Secteur;
use Sogedial\SiteBundle\Entity\Departement;
use Sogedial\SiteBundle\Entity\Famille;
use Sogedial\SiteBundle\Entity\SousFamille;
use Sogedial\SiteBundle\Entity\Marque;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\RechercheMot;
use Sogedial\SiteBundle\Entity\Stock;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Validator\Constraints\DateTime;

class ImportProduitCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importProduitCsv',
            'Import produit from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'produits';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    private function issue($lineno, $output, $msg)
    {
        //$output->writeln('Error line '.$lineno.' - '.$msg);
    }

    protected function importRegionQuatre($data, OutputInterface $output)
    {
        $i = 0;
        $now_date = new \DateTime('now');
        $skipped = 0;
        $critical = 0;
        $societe_site = $this->getSocieteSite();
        $currentRegion = $this->getRegionNumeric();

        //Begin region 4
        $arrayCodeDispoExcluded = array('S', 'T', 'V');
        $arrayCodeFamilleExcluded = array('262', '264', '265', '266', '267', '268', '269', '270', '271', '272');

        $arrayExcluded = array('.', '..', '...');

        //End region 4

        $region = parent::$em->getRepository('SogedialSiteBundle:Region')
            ->findOneBy(array('code' => $currentRegion));

        $codes = [];
        foreach ($data as $row) {
            // compatibilité avec les "anciens" fichiers
            // si pas de champ "société" dans le CSV des produits, on prend la société reglée dans les paramètres globaux
            if (!isset($row[40])) {
                $skipped++;
                $this->issue($i, $output, "Invalid value for society code'.");
                //si pas de code societe => skip
                continue;
            }


            //Begin region 4
            $societe_row = 401;
            //End region 4
            $nouveau_code_produit = $societe_row . '-' . $row[0];
            $pcb = explode('.', trim($row[38]));
            if ($societe_row === '301' && intval($pcb[0]) == 0) {
                $skipped++;
                continue;
            }

            if (strlen($row[0]) > 7) {
                // VARCHAR(11) ne sera pas suffisant!
                // INGESTION_ERROR
                $this->issue($i, $output, "Critical: product code too long: '" . $row[0] . "'");
                $critical++;
            }

            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $nouveau_code_produit));

            $departement = parent::$em->getRepository('SogedialSiteBundle:Departement')
                ->findOneBy(array('code' => $societe_row . '-' . $row[1]));

            // $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
            //     ->findOneBy(array('code' => $currentRegion.'-'.$row[2] ));

            // risque de collision (théorique) -> Eviter avec la region ? 
            // $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
            //     ->findOneBy(array('valeur' => $row[3], 'secteur' => $currentRegion.'-'.$row[2], 'region' => intval($region->getCode())));

            $marque = parent::$em->getRepository('SogedialSiteBundle:Marque')
                ->findOneBy(array('code' => $row[4]));

            $famille = parent::$em->getRepository('SogedialSiteBundle:Famille')
                ->findOneBy(array('code' => $currentRegion . '-' . $row[5]));

            // pour éviter le risque de collision, exemple (Sofrigu): "AB"."DC" et "AP"."DC"
            $sousFamille = parent::$em->getRepository('SogedialSiteBundle:SousFamille')
                ->findOneBy(array('valeur' => $row[6], 'famille' => $currentRegion . '-' . $row[5], 'region' => intval($region->getCode())));

            // idem
            $segment = parent::$em->getRepository('SogedialSiteBundle:Segment')
                ->findOneBy(array('valeur' => $row[7], 'sousFamille' => $currentRegion . '-' . $row[5] . $row[6]));

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $societe_row));

            $supplier = parent::$em->getRepository('SogedialSiteBundle:Supplier')
                ->findOneBy(array('code' => $societe_row . '-' . $row[39]));

            $isNewProduct = false;
            if (!($produit instanceof Produit)) {
                $produit = new Produit();
                $produit->setCode($nouveau_code_produit);
                $produit->setCreatedAt($now_date);
                $isNewProduct = true;
            }

            $actif = "1";

            if (in_array($row[57], $arrayCodeDispoExcluded) OR (($row[41] == '71746') && (in_array($row[5], $arrayCodeFamilleExcluded)))) {
                $actif = "0";
            }
            if ($actif === "0.0") {
                $actif = "0";
            }
            if ($actif === "1.0") {
                $actif = "1";
            }

            if ($actif !== "0" && $actif !== "1") {
                $this->issue($i, $output, "Invalid value for field 'actif'.");
            }

            $meta_ligne = "";
            if ($departement instanceof Departement) {
                $produit->setDepartement($departement);
                $meta_ligne .= ' rayon:"' . $departement->getLibelle() . '" ';
            } else if ($actif === "1") {
                $this->issue($i, $output, "'Departement' not found: '" . $row[1] . "'");
            }

            // if ($secteur instanceof Secteur) {
            //     $produit->setSecteur($secteur);
            //     $meta_ligne.=' rayon:"'.$secteur->getLibelle().'" ';
            // } else if ($actif === "1") {
            //     $this->issue($i, $output, "'Secteur' not found: '".$row[2]."'");
            // }

            // if ($rayon instanceof Rayon) {
            //     $produit->setRayon($rayon);
            //     $meta_ligne.=' rayon:"'.$rayon->getLibelle().'" ';
            // } else if ($actif === "1") {
            //     $this->issue($i, $output, "'Rayon' not found: '".$row[3]."'");
            // }

            $defaultMarque = parent::$em->getRepository('SogedialSiteBundle:Marque')->findOneBy(array('code' => '***'));
            if ($marque instanceof Marque) {
                $produit->setMarque($marque);
                $meta_ligne .= ' marque:"' . $marque->getLibelle() . '" ';
            } else {
                $produit->setMarque($defaultMarque);
                $meta_ligne .= ' marque:"' . $defaultMarque->getLibelle() . '" ';
            }

            if ($famille instanceof Famille) {
                $produit->setFamille($famille);
                $meta_ligne .= ' rayon:"' . $famille->getLibelle() . '" ';
            } else if ($actif === "1") {
                $this->issue($i, $output, "'Famille' not found: '" . $row[5] . "'");
            }

            if ($sousFamille instanceof SousFamille) {
                $produit->setSousFamille($sousFamille);
                $meta_ligne .= ' rayon:"' . $sousFamille->getLibelle() . '" ';
            }

            if ($segment instanceof Segment) {
                $produit->setSegment($segment);
                $meta_ligne .= ' rayon:"' . $segment->getLibelle() . '" ';
            }

            // TODO truncate de recherche_mot et produit_recherche_mot ? car sinon, les anciens liens restent toujours...
            // indexation pour la recherche
            $rms = parent::$srch->searchExplode($meta_ligne . utf8_encode($row[8]));     // les mots de la "dénomination prooduit base"
            $recherche_mots = array();
            foreach ($rms as $rm) {
                // 1 = dénomination
                $recherche_mot = parent::$em->getRepository('SogedialSiteBundle:RechercheMot')
                    ->findOneBy(array('phonetique' => $rm['phonetique'], 'provenance' => $rm['provenance']));

                if (!($recherche_mot instanceof RechercheMot)) {
                    $recherche_mot = new RechercheMot($rm['phonetique'], $rm['provenance']);
                    parent::$em->persist($recherche_mot);
                    parent::$em->flush();                   // pas trouvé d'autres moyens de faire d'une sorte que getRepository() trouve le même mot dès le prochain appel

                    // TODO idée d'optimisation : repository->getall dans un tableau ; parcourir les produits ; ajouter dans le tableau ; ensuite parcourir le tableau + persist sur les nouveaux
                    // suivi d'un seul flush, et après on peut faire un pass produits classique. En gros, refaire ORM correctement :)
                }

                $recherche_mots[] = $recherche_mot;
                // ne marche pas UPDATE: probablement parce que je ne faisais pas le flush ? - parent::$em->merge($recherche_mot);                 // créer une nouvelle entité si celle-là n'existe pas, mettre à jour l'existante sinon
            }
            $produit->setRechercheMots($recherche_mots);
            $produit->setDenominationProduitBase(utf8_encode(trim($row[8])));
            $produit->setDenominationProduitLong(utf8_encode(trim($row[9])));
            $produit->setDenominationProduitCourt(utf8_encode(trim($row[10])));
            $produit->setDenominationProduitCaisse(utf8_encode(trim($row[11])));
            $produit->setPoidsVariable($row[12]);
            $produit->setDescription($row[13]);
            $produit->setFormat($row[14]);
            $produit->setDlc($row[15]);
            $produit->setDlcMoyenne($row[16]);
            $produit->setDlcGarantie($row[17]);
            $produit->setEan13($row[18]);
            $produit->setIngredients($row[19]);
            $produit->setRhf($row[20]);
            $produit->setOrigine($row[21]);
            $produit->setInAchatLogidis(true);
            $produit->setDeletedAt(NULL);

            // $temperature = trim($row[22]);
            // if($temperature === 'CONGELE' || $temperature === 'FROID' ){
            //     $temperature = 'SURGELE';
            // } else if($temperature === 'POSITIF'){
            //     $temperature = 'FRAIS';
            // }
            // $produit->setTemperature($temperature);

            // Begin region 4
            if (in_array($row[47], array('71744', '71746', '89063'))) {
                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    ->findOneBy(array('code' => $currentRegion . '-011'));

                $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                    ->findOneBy(array('code' => $currentRegion . '-01'));

                $temperature = 'SEC';
            } elseif ($row[47] == '88884') {
                $temperature = 'SURGELE';
                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    ->findOneBy(array('code' => $currentRegion . '-022'));

                $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                    ->findOneBy(array('code' => $currentRegion . '-02'));
            } else {
                $temperature = 'FRAIS';

                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    ->findOneBy(array('code' => $currentRegion . '-033'));
                $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                    ->findOneBy(array('code' => $currentRegion . '-03'));
            }

            if ($secteur instanceof Secteur) {
                $produit->setSecteur($secteur);
            }

            if ($rayon instanceof Rayon) {
                $produit->setRayon($rayon);
            }

            $produit->setTemperature($temperature);

            // End region 4


            $produit->setNdp($row[24]);
            if ($produit->getBlacklisted() === true) {
                $produit->setActif(false);
            } else {
                $produit->setActif($actif);
            }
            $produit->setMarketingCode($row[26]);
            if ($isNewProduct) {
                $produit->setNatureCode("NOUVEAUTE");
            } else {
                $produit->setNatureCode(utf8_encode($row[27]));
            }
            $produit->setSpecialSuivi($row[28]);
            $produit->setTvaCode($row[29]);
            $produit->setContenance($row[31]);
            $produit->setSaleUnity($row[32]);
            $produit->setSaleCommandMesureUnity($row[33]);
            $produit->setAlcool($row[34]);
            $produit->setLiquide($row[35]);
            //$produit->setStartedAt(new \DateTime($row[36]));  // commenté car le fichier peut contenir les dates invalides (30/02 ou 32/12...), de toute façon, pas utilisé aujourd'hui
            //$produit->setEndedAt(new \DateTime($row[37]));
            //163 =  sterling pound 
            $produit->setPreCommande(array_key_exists(45, $row) && ord($row[45]) === 163);

            if ($entreprise instanceof Entreprise) {
                $produit->setEntreprise($entreprise);
            }

            if ($supplier instanceof Supplier) {
                $produit->addSupplier($supplier);
            }
            $produit->setPcb($pcb[0]);

            if (intval($pcb[0]) <= 0) {
                // TODO ajouter vérification pour les pcb non-entiers
                if ($actif === "1") {
                    $this->issue($i, $output, "Negative or zero value for field 'pcb'.");
                }
            }

            if (substr($row[8], 28, 2) === "KG") {
                $produit->setSaleUnity('KILOGRAMME');
                $produit->setPoidsVariable('OUI');
            }

            $produit->setUpdatedAt($now_date);
            $produit->setDureeVieJours($row[53]);
            parent::$em->persist($produit);

            //Begin region 4 - faux stock

            $stockColis = 50000;
            $stockUc = 50000;

            $stock = parent::$em->getRepository('SogedialSiteBundle:Stock')
                ->findOneBy(array('code' => "401" . $row[0]));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $currentRegion));

            if (!($stock instanceof Stock)) {
                $stock = new Stock();
                $stock->setCode("401" . $row[0]);
            }

            if ($entreprise instanceof Entreprise) {
                $stock->setEntreprise($entreprise);
            }

            if ($region instanceof Region) {
                $stock->setRegion($region);
            }

            if ($produit instanceof Produit) {
                $stock->setProduit($produit);
            }

            //Variable utilisé pour le calcul tarif marge
            if (array_key_exists(52, $row) && isset($row[52])) {
                $produit->setPrixPreste($row[52]);
            }

            $stock->setStockTheoriqueColis($stockColis);
            $stock->setStockTheoriqueUc($stockUc);

            parent::$em->persist($stock);

            //End region 4 - faux stock

            parent::advance($i, $output);
            $i++;
        }

        $this->finish();
        $output->writeln('');
        $output->writeln(($critical ? $critical : "No") . ' critical errors in "produit" entries.');
        return $skipped;
    }

    protected function import($data, OutputInterface $output)
    {
        parent::$em->getRepository('SogedialSiteBundle:Produit')->resetInFlux($this->getRegionNumeric());

        if ($this->getRegionNumeric() === "4") {
            $this->importRegionQuatre($data, $output);
        } else {
            $i = 0;
            $now_date = new \DateTime('now');
            $skipped = 0;
            $critical = 0;
            $societe_site = $this->getSocieteSite();
            $currentRegion = $this->getRegionNumeric();

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $currentRegion));

            $codes = [];
            foreach ($data as $row) {
                // compatibilité avec les "anciens" fichiers
                // si pas de champ "société" dans le CSV des produits, on prend la société reglée dans les paramètres globaux
                if (!isset($row[40])) {
                    $skipped++;
                    $this->issue($i, $output, "Invalid value for society code'.");
                    //si pas de code societe => skip
                    continue;
                }


                $societe_row = isset($row[40]) ? $row[40] : $societe_site;
                $nouveau_code_produit = $societe_row . '-' . $row[0];
                $pcb = explode('.', trim($row[38]));

                if ($societe_row === '301' && intval($pcb[0]) == 0) {
                    $skipped++;
                    continue;
                }

                if (strlen($row[0]) > 7) {
                    // VARCHAR(11) ne sera pas suffisant!
                    // INGESTION_ERROR
                    $this->issue($i, $output, "Critical: product code too long: '" . $row[0] . "'");
                    $critical++;
                }

                $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                    ->findOneBy(array('code' => $nouveau_code_produit));

                $departement = parent::$em->getRepository('SogedialSiteBundle:Departement')
                    ->findOneBy(array('code' => $row[1]));

                $secteur = parent::$em->getRepository('SogedialSiteBundle:Secteur')
                    ->findOneBy(array('code' => $currentRegion . '-' . $row[2]));

                // risque de collision (théorique) -> Eviter avec la region ?
                $rayon = parent::$em->getRepository('SogedialSiteBundle:Rayon')
                    ->findOneBy(array('valeur' => $row[3], 'secteur' => $currentRegion . '-' . $row[2], 'region' => intval($region->getCode())));

                $marque = parent::$em->getRepository('SogedialSiteBundle:Marque')
                    ->findOneBy(array('code' => $row[4]));

                $famille = parent::$em->getRepository('SogedialSiteBundle:Famille')
                    ->findOneBy(array('code' => $currentRegion . '-' . $row[5]));

                // pour éviter le risque de collision, exemple (Sofrigu): "AB"."DC" et "AP"."DC"
                $sousFamille = parent::$em->getRepository('SogedialSiteBundle:SousFamille')
                    ->findOneBy(array('valeur' => $row[6], 'famille' => $currentRegion . '-' . $row[5], 'region' => intval($region->getCode())));

                // idem
                $segment = parent::$em->getRepository('SogedialSiteBundle:Segment')
                    ->findOneBy(array('valeur' => $row[7], 'sousFamille' => $currentRegion . '-' . $row[5] . $row[6]));

                $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                    ->findOneBy(array('code' => $societe_row));

                $supplier = parent::$em->getRepository('SogedialSiteBundle:Supplier')
                    ->findOneBy(array('code' => $societe_row . '-' . $row[39]));

                if (!($produit instanceof Produit)) {
                    $produit = new Produit();
                    $produit->setCode($nouveau_code_produit);
                    $produit->setCreatedAt($now_date);
                }

                $actif = $row[25];
                if ($actif === "0.0") {
                    $actif = "0";
                }
                if ($actif === "1.0") {
                    $actif = "1";
                }

                if ($actif !== "0" && $actif !== "1") {
                    $this->issue($i, $output, "Invalid value for field 'actif'.");
                }

                $meta_ligne = "";
                if ($departement instanceof Departement) {
                    $produit->setDepartement($departement);
                    $meta_ligne .= ' rayon:"' . $departement->getLibelle() . '" ';
                } else if ($actif === "1") {
                    $this->issue($i, $output, "'Departement' not found: '" . $row[1] . "'");
                }

                if ($secteur instanceof Secteur) {
                    $produit->setSecteur($secteur);
                    $meta_ligne .= ' rayon:"' . $secteur->getLibelle() . '" ';
                } else if ($actif === "1") {
                    $this->issue($i, $output, "'Secteur' not found: '" . $row[2] . "'");
                }

                if ($rayon instanceof Rayon) {
                    $produit->setRayon($rayon);
                    $meta_ligne .= ' rayon:"' . $rayon->getLibelle() . '" ';
                } else if ($actif === "1") {
                    $this->issue($i, $output, "'Rayon' not found: '" . $row[3] . "'");
                }


                $defaultMarque = parent::$em->getRepository('SogedialSiteBundle:Marque')->findOneBy(array('code' => '***'));
                if ($marque instanceof Marque) {
                    $produit->setMarque($marque);
                    $meta_ligne .= ' marque:"' . $marque->getLibelle() . '" ';
                } else {
                    $produit->setMarque($defaultMarque);
                    $meta_ligne .= ' marque:"' . $defaultMarque->getLibelle() . '" ';
                }

                if ($famille instanceof Famille) {
                    $produit->setFamille($famille);
                    $meta_ligne .= ' rayon:"' . $famille->getLibelle() . '" ';
                } else if ($actif === "1") {
                    $this->issue($i, $output, "'Famille' not found: '" . $row[5] . "'");
                }

                if ($sousFamille instanceof SousFamille) {
                    $produit->setSousFamille($sousFamille);
                    $meta_ligne .= ' rayon:"' . $sousFamille->getLibelle() . '" ';
                }

                if ($segment instanceof Segment) {
                    $produit->setSegment($segment);
                    $meta_ligne .= ' rayon:"' . $segment->getLibelle() . '" ';
                }

                // TODO truncate de recherche_mot et produit_recherche_mot ? car sinon, les anciens liens restent toujours...
                // indexation pour la recherche
                $rms = parent::$srch->searchExplode($meta_ligne . utf8_encode($row[8]));     // les mots de la "dénomination prooduit base"
                $recherche_mots = array();
                foreach ($rms as $rm) {
                    // 1 = dénomination
                    $recherche_mot = parent::$em->getRepository('SogedialSiteBundle:RechercheMot')
                        ->findOneBy(array('phonetique' => $rm['phonetique'], 'provenance' => $rm['provenance']));

                    if (!($recherche_mot instanceof RechercheMot)) {
                        $recherche_mot = new RechercheMot($rm['phonetique'], $rm['provenance']);
                        parent::$em->persist($recherche_mot);
                        parent::$em->flush();                   // pas trouvé d'autres moyens de faire d'une sorte que getRepository() trouve le même mot dès le prochain appel

                        // TODO idée d'optimisation : repository->getall dans un tableau ; parcourir les produits ; ajouter dans le tableau ; ensuite parcourir le tableau + persist sur les nouveaux
                        // suivi d'un seul flush, et après on peut faire un pass produits classique. En gros, refaire ORM correctement :)
                    }

                    $recherche_mots[] = $recherche_mot;
                    // ne marche pas UPDATE: probablement parce que je ne faisais pas le flush ? - parent::$em->merge($recherche_mot);                 // créer une nouvelle entité si celle-là n'existe pas, mettre à jour l'existante sinon
                }
                $produit->setRechercheMots($recherche_mots);
                $produit->setDenominationProduitBase(utf8_encode(trim($row[8])));
                $produit->setDenominationProduitLong(utf8_encode(trim($row[9])));
                $produit->setDenominationProduitCourt(utf8_encode(trim($row[10])));
                $produit->setDenominationProduitCaisse(utf8_encode(trim($row[11])));
                $produit->setPoidsVariable($row[12]);
                $produit->setDescription($row[13]);
                $produit->setFormat($row[14]);
                $produit->setDlc($row[15]);
                $produit->setDlcMoyenne($row[16]);
                $produit->setDlcGarantie($row[17]);
                $produit->setEan13($row[18]);
                $produit->setIngredients($row[19]);
                $produit->setRhf($row[20]);
                $produit->setOrigine($row[21]);
                $produit->setInAchatLogidis(true);
                $produit->setDeletedAt(NULL);

                $temperature = trim($row[22]);
                if ($temperature === 'CONGELE' || $temperature === 'FROID') {
                    $temperature = 'SURGELE';
                } else if ($temperature === 'POSITIF') {
                    $temperature = 'FRAIS';
                }

                $produit->setTemperature($temperature);

                $produit->setNdp($row[24]);
                //TODO : Reactivez les produits de la precommande avion quand on les gérera
                if (($produit->getBlacklisted() === true) || (array_key_exists(45, $row) && ord($row[45]) === 163 && $produit->getNatureCode() === 'AVION')) {
                    $produit->setActif(false);
                } else {
                    $produit->setActif($actif);
                }

                $produit->setMarketingCode($row[26]);
                $produit->setNatureCode(utf8_encode($row[27]));
                $produit->setSpecialSuivi($row[28]);
                $produit->setTvaCode($row[29]);
                $produit->setContenance($row[31]);
                $produit->setSaleUnity($row[32]);
                $produit->setSaleCommandMesureUnity($row[33]);
                $produit->setAlcool($row[34]);
                $produit->setLiquide($row[35]);
                //$produit->setStartedAt(new \DateTime($row[36]));  // commenté car le fichier peut contenir les dates invalides (30/02 ou 32/12...), de toute façon, pas utilisé aujourd'hui
                //$produit->setEndedAt(new \DateTime($row[37]));
                //163 =  sterling pound

//                if($societe_row === '110') { //TODO : cas Logigua
//                    $produit->setPreCommande(($societe_row == 110) ? 0 : array_key_exists(45, $row) && (ord($row[45]) === 163 || $row[45] === '8' ) && ($produit->getNatureCode() === 'ECLAT/PRE CDE' || $produit->getNatureCode() === 'AVION'));
//                } else { // TODO : tous les autres cas
//                    $produit->setPreCommande(($societe_row == 130) ? 0 : array_key_exists(45, $row) && ord($row[45]) === 163 && ($produit->getNatureCode() === 'ECLAT/PRE CDE' || $produit->getNatureCode() == 'AVION'));
//                }

                if ($societe_row === '110') { //TODO : cas Logigua
                    $produit->setPreCommande((array_key_exists(45, $row) && (ord($row[45]) === 163 || $row[45] === '8') && ($produit->getNatureCode() === 'ECLAT/PRE CDE' || $produit->getNatureCode() === 'AVION'))) ? 1 : 0;
                } else { // TODO : tous les autres cas
                    $produit->setPreCommande((array_key_exists(45, $row) && ord($row[45]) === 163 && ($produit->getNatureCode() === 'ECLAT/PRE CDE' || $produit->getNatureCode() == 'AVION'))) ? 1 : 0;
                }

                if ($entreprise instanceof Entreprise) {
                    $produit->setEntreprise($entreprise);
                }

                if ($supplier instanceof Supplier) {
                    $produit->addSupplier($supplier);
                }

                if (intval($pcb[1] > 0)) {
                    $produit->setPcb(sprintf('%s.%s', $pcb[0], $pcb[1]));
                } else {
                    $produit->setPcb($pcb[0]);
                }

                if (intval($pcb[0]) <= 0) {
                    // TODO ajouter vérification pour les pcb non-entiers
                    if ($actif === "1") {
                        $this->issue($i, $output, "Negative or zero value for field 'pcb'.");
                    }
                }

                if (substr($row[8], 28, 2) === "KG") {
                    $produit->setSaleUnity('KILOGRAMME');
                    $produit->setPoidsVariable('OUI');
                }

                $produit->setUpdatedAt($now_date);
                parent::$em->persist($produit);

                $arrayCodeEntreprise = array('150', '250');

                // TODO : starting inserting stock for none stock entreprise
                if (in_array($entreprise->getCode(), $arrayCodeEntreprise)) {
                    $this->setStockByEntreprise($produit, $currentRegion, $entreprise, $row);
                }

                parent::advance($i, $output);
                $i++;
            }
            var_dump($codes);
            $this->finish();
            $output->writeln('');
            $output->writeln(($critical ? $critical : "No") . ' critical errors in "produit" entries.');
        }

        parent::$em->getRepository('SogedialSiteBundle:Produit')->disableProdutsByRegion($this->getRegionNumeric());
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMARTIP.CSV', ',');

        if ($data !== false && $data !== null) {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PRODUIT.CSV', ',');

        return $data;
    }

    /**
     * @param $produit
     * @param $currentRegion
     * @param $entreprise
     * @param $row
     */
    private function setStockByEntreprise($produit, $currentRegion, $entreprise, $row)
    {
        //Begin entreprise 150 && 250 - fake stock

        $stockColis = 50000;
        $stockUc = 50000;

        $stock = parent::$em->getRepository('SogedialSiteBundle:Stock')
            ->findOneBy(array('code' => $entreprise->getCode() . $row[0]));

        $region = parent::$em->getRepository('SogedialSiteBundle:Region')
            ->findOneBy(array('code' => $currentRegion));

        if (!($stock instanceof Stock)) {
            $stock = new Stock();
            $stock->setCode($entreprise->getCode() . $row[0]);
        }

        if ($entreprise instanceof Entreprise) {
            $stock->setEntreprise($entreprise);
        }

        if ($region instanceof Region) {
            $stock->setRegion($region);
        }

        if ($produit instanceof Produit) {
            $stock->setProduit($produit);
        }

        $stock->setStockTheoriqueColis($stockColis);
        $stock->setStockTheoriqueUc($stockUc);

        parent::$em->persist($stock);

        //End entreprise 150 && 250 - fake stock
    }
}

