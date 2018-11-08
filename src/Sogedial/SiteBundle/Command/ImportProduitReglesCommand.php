<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Assortiment;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\ProduitRegle;
use Sogedial\SiteBundle\Entity\RegleMOQ;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Supplier;
use Sogedial\SiteBundle\Entity\Tarification;
use Sogedial\SiteBundle\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportProduitReglesCommand extends ImporterManager
{
    private static $rulesMOQ;

    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importMOQCsv',
            'Import & apply MOQ Rules from CSV file on products'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'Produits Regles';
        $this->executeCmd($this, $commandeName, $input, $output);        
    }

    private function issue($lineno, $output, $msg)
    {
        $output->writeln('Error line '.$lineno.' - '.$msg);
    }

    private function setGroupOrMixInRuleMOQ($flagGroupOrMix, $ean13Product, $quantity, $unity, $supplierCode){
        $group = $flagGroupOrMix === true;
        $mix = $flagGroupOrMix === false;

        $ruleGroupExist = false;

        foreach(self::$rulesMOQ[$supplierCode] as $index => $rule){
            if(
                $rule["quantity"] === $quantity &&
                $rule["unity"] === $unity &&
                $rule["mix"] === $mix &&
                $rule["group"] === $group
            ){
                array_push(self::$rulesMOQ[$supplierCode][$index]["ean13"], $ean13Product);
                break;
            }
        }

        if($ruleGroupExist === false){
            array_push(
                self::$rulesMOQ[$supplierCode],
                array(
                    "quantity" => $quantity,
                    "unity" => $unity,
                    "mix" => $mix,
                    "group" => $group,
                    "ean13" => array(
                        $ean13Product
                    )
                )
            );
        }
    }

    protected function import($data, OutputInterface $output)
    {
        $produitsRepository = parent::$em->getRepository(
            'SogedialSiteBundle:Produit'
        );
        $moqRepository = parent::$em->getRepository(
            'SogedialSiteBundle:RegleMOQ'
        );
        $produitRegleRepository = parent::$em->getRepository(
            'SogedialSiteBundle:ProduitRegle'
        );
        $supplierRepository = parent::$em->getRepository(
            'SogedialSiteBundle:Supplier'
        );

        $i = 0;
        self::$rulesMOQ = array();

        /*
        [
            <supplier> => [
                [
                    quantite = <integer>
                    unite = <string>
                    mix = <boolean>
                    groupe = <boolean>
                    ean13 => [
                        <ean13>,
                        ...
                    ]
                ],
                ...
            ],
            ...
        ]
        */
        $suppliersObjets = array();
        $productsTreated = array();

        $output->writeln('Importation des fournisseurs manquants...');

        foreach($data as $row){
            $s = $row[4];
            $row[5] = trim($row[5]);
            $supplier = $supplierRepository->findOneBy(
                array('nom' => $row[5])
            );

            if(!array_key_exists($s, $suppliersObjets)){
                //new supplier
                if($supplier === NULL){
                    $supplier = new Supplier();
                    $supplier->setCode($s);
                    $supplier->setNom($row[5]);
                    parent::$em->persist($supplier);
                }
                $suppliersObjets[$s] = $supplier;
            }
        }
        parent::$em->flush();
    
        $output->writeln('Importation des fournisseurs manquants FIN');
        $output->writeln('Création de la logique des règles ...');

        //rulesMOQ
        foreach($data as $row){
            $i++;
            parent::advance($i, $output);
            $row[0] = str_replace(" ", "", $row[0]);
            $rule = explode(" ", $row[3]);

            if($rule[0] !== 'Cde' && $rule[0] !== 'Mini'){
                continue;
            }

            //clean name string
            $row[5] = trim($row[5]);
            $s = $row[4];

            if(!isset(self::$rulesMOQ[$s])){
                self::$rulesMOQ[$s] = array();
            }

            $quantityMatch = array();
            preg_match('/\d+/', $row[3], $quantityMatch);
            $quantity = intval(current($quantityMatch));

            $unity = false;
            if(strpos($row[3], 'UC') || strpos($row[3], 'uc')){
                $unity = 'uc';
            } elseif(strpos($row[3], 'col') || strpos($row[3], 'COL')){
                $unity = 'col';
            } elseif(strpos($row[3], 'pal') || strpos($row[3], 'PAL')){
                $unity = 'pal';
            } elseif(strpos($row[3], 'kg') || strpos($row[3], 'KG') || strpos($row[3], 'Kg')){
                $unity = 'kg';
            } elseif(strpos($row[3], chr(128))){
                $unity = 'euros';
            }

            $mix = false;
            $group = false;
            if(strpos($row[3], 'grp') || strpos($row[3], 'grop')){
                $group = true;
                $this->setGroupOrMixInRuleMOQ(true, $row[0], $quantity, $unity, $s);
            } elseif(strpos($row[3], 'mix')){
                $mix = true;
                $this->setGroupOrMixInRuleMOQ(false, $row[0], $quantity, $unity, $s);
            } else {
                array_push(
                    self::$rulesMOQ[$s],
                    array(
                        "quantity" => $quantity,
                        "unity" => $unity,
                        "mix" => $mix,
                        "group" => $group,
                        "ean13" => array(
                            $row[0]
                        )
                    )
                );
            }
        }

        $output->writeln('Création de la logique des règles FIN');
        $output->writeln('Importation de la logique des règles ...');

        foreach(self::$rulesMOQ as $supplierCode => $rulesMOQ){
            foreach($rulesMOQ as $ruleMOQ){
                $rule = $moqRepository->findOneBy(array(
                    "supplier" => $suppliersObjets[$supplierCode]->getCode(),
                    "quantiteMinimale" => $ruleMOQ["quantity"],
                    "unite" => $ruleMOQ["unity"],
                    "mix" => $ruleMOQ["mix"],
                    "group" => $ruleMOQ["group"]
                ));

                if($rule === null){
                    $rule = new RegleMOQ();
                } else {
                    continue;
                }

                $rule->setSupplier($suppliersObjets[$supplierCode]);
                $rule->setQuantiteMinimale($ruleMOQ["quantity"]);
                $rule->setUnite($ruleMOQ["unity"]);
                $rule->setMix($ruleMOQ["mix"]);
                $rule->setGroup($ruleMOQ["group"]);
                parent::$em->merge($rule);
            }
        }
        parent::$em->flush();
        parent::$em->clear();

        $output->writeln('Importation de la logique des règles FIN');
        $output->writeln('Association des règles sur les produits ...');

        foreach(self::$rulesMOQ as $supplierCode => $rulesMOQ){
            foreach($rulesMOQ as $ruleMOQ){
                $rule = $moqRepository->findOneBy(array(
                    "supplier" => $suppliersObjets[$supplierCode]->getCode(),
                    "quantiteMinimale" => $ruleMOQ["quantity"],
                    "unite" => $ruleMOQ["unity"],
                    "mix" => $ruleMOQ["mix"],
                    "group" => $ruleMOQ["group"]
                ));
                foreach($ruleMOQ["ean13"] as $ean13){
                    $productsByEan13 = $produitsRepository->findBy(
                        array("ean13" => $ean13)
                    );

                    foreach($productsByEan13 as $product){                        
                        if(in_array($product->getCode(), $productsTreated)){
                            continue;
                        } else{
                            array_push($productsTreated, $product->getCode());
                        }
                        //get products from ean13, can get one to many
                        $productRule = $produitRegleRepository->findOneBy(array(
                            "regle" => $rule->getCode(),
                            "code" => $product->getCode()
                        ));

                        if(!$productRule){
                            $productRule = new ProduitRegle();
                        } else {
                            continue;
                        }

                        $productRule->setRegle($rule);
                        $productRule->setCode($product);
                        parent::$em->persist($productRule);
                    }
                }
            }
        }

        parent::$em->flush();
        $output->writeln('Association des règles sur les produits FIN');
    }

    /*
    * @param string moqRuleString
    *
    private function setProduitRegle($moqRuleString, Supplier $supplier, Produit $product, ProduitRegle &$produitRegle){
        $moqRuleType = 0;
        $moqRuleMinimalQuantity = 0;

        $produitRegle->setCode($product);
        $produitRegle->setSupplier($supplier);
        $produitRegle->setTypeMoq($moqRuleString);
        $produitRegle->setQuantiteMinimale($moqRuleString);
    }
    */
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/moq-produit-fournisseur.csv', ';', true);

        if ($data !== false){
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/moq-produit-fournisseur.csv', ';', true);

        return $data;
    }
}