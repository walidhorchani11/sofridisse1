<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Entreprise;
use Sogedial\SiteBundle\Entity\Region;
use Sogedial\SiteBundle\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportSupplierCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importSupplierCsv',
            'Import supplier from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'fournisseur';
        $this->executeCmd($this, $commandeName, $input, $output);    
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $skipped = 0;
        $critical = 0;
        $societe_site = $this->getSocieteSite();

        foreach ($data as $row) {
            $societe_num = parent::$ms->getSocieteNumByAlpha($row[8]);
            $codeSupplier = parent::$em->getRepository('SogedialSiteBundle:Supplier')
                ->findOneBy(array('code' => $societe_num.'-'.$row[0]));

            $region = parent::$em->getRepository('SogedialSiteBundle:Region')
                ->findOneBy(array('code' => $row[7]));

            $entreprise = parent::$em->getRepository('SogedialSiteBundle:Entreprise')
                ->findOneBy(array('code' => $societe_num));

            if ($codeSupplier instanceof Supplier) {
                $codeSupplier->setAppro($row[1]);
                $codeSupplier->setIndicator11($row[2]);
                $codeSupplier->setOriginalCode($row[3]);
                $codeSupplier->setFrenqunecy($row[6]);
                $codeSupplier->setApproDelay($row[5]);

                if ($region instanceof Region) {
                    $codeSupplier->setRegion($region);
                }

                if ($entreprise instanceof Entreprise) {
                    $codeSupplier->setEntreprise($entreprise);
                }

                $codeSupplier->setNom(utf8_encode($row[9]));
                parent::$em->persist($codeSupplier);
                
            } else {
                $supplier = new Supplier();
                $supplier->setCode($societe_num.'-'.$row[0]);
                $supplier->setAppro($row[1]);
                $supplier->setIndicator11($row[2]);
                $supplier->setOriginalCode($row[3]);
                $supplier->setFrenqunecy($row[6]);
                $supplier->setApproDelay($row[5]);

                if ($region instanceof Region) {
                    $supplier->setRegion($region);
                }

                if ($entreprise instanceof Entreprise) {
                    $supplier->setEntreprise($entreprise);
                }

                $supplier->setNom(utf8_encode($row[9]));

                parent::$em->persist($supplier);
            }

            parent::advance($i, $output);
            $i++;
        }
        $this->finish();
        $output->writeln('');
        $output->writeln($skipped.' "supplier" entries skipped.' );                
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMFOURP.CSV', ',');

        if ($data!==false  && $data !== null)
        {
            return $data;
        }
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/FOURNISS.CSV', ',');

        return $data;
    }
}