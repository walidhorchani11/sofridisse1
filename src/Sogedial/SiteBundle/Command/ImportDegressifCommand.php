<?php

namespace Sogedial\SiteBundle\Command;

use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Degressif;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportDegressifCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importDegressifCsv',
            'Import dégressivité from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'degressif';
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
        $max_paliers = 5;
        $societe_site = $this->getSocieteSite();

        foreach ($data as $row)
        {
            $produit = parent::$em->getRepository('SogedialSiteBundle:Produit')
                ->findOneBy(array('code' => $row[0]."-".$row[1]));

            $p = 0;
            $prevPrixHT = 0.0;

            if (trim($row[3])=="" || floatval(trim($row[3])) <= 0.0)
            {
                //$this->issue($i, $output, "Degressif p1 price HT is null, 0.0 or negative.");
                $skipped++;
                continue;
            }

            for ($p = 2; $p < 2 + 2 * $max_paliers; $p += 2)
            {
                // on s'attend à des paliers entiers
                $palier = intval(trim($row[$p]));
                $price = floatval(trim($row[$p+1]));

                if($row[0] === '301' && $palier === 0){
                    continue;
                }

                $palier_parts = explode('.', trim($row[$p]));
                if ($palier_parts[1] && ($palier_parts[1]!=="00"))      // cela laissera passer sans erreur "0" (car faux), "00" et absence de "." (car faux)
                {
                    //$this->issue($i, $output, "Unexpected fractional value for field 'palier'.");
                }
                if ($palier < 0 || $palier > 999999)    // maximum 6 caractères est prévu pour le palier
                {
                    //$this->issue($i, $output, "Invalid value for field 'palier'.");
                }

                // le code d'un tarif degressif = concatenation de la société, du produit et du palier
                // exemple : 301-000072-12
                // le deuxième tiret est important pour ne pas confondre le code produit et le palier (00007-21 vs. 000072-1)
                $code_degressif = $row[0]."-".$row[1]."-".$palier;

                // on n'ajoute que des nouveaux paliers
                if(($p==2) || ( $palier > intval(trim($row[$p-2]))))
                {
                    $degressif = parent::$em->getRepository('SogedialSiteBundle:Degressif')->findOneBy(array('code' => $code_degressif));

                    if (!($degressif instanceof Degressif))
                    {
                        $degressif = new Degressif();
                        $degressif->setCode($code_degressif);
                        $degressif->setCreatedAt(new \DateTime('now'));
                    }

                    if ($produit instanceof Produit){
                        $degressif->setProduit($produit);
                    }				
                                
                    $degressif->setPalier($palier);    					// integer dans la base
                    $degressif->setPrixHT($price);                     // float dans la base
                    $degressif->setUpdatedAt(new \DateTime('now'));

                    parent::$em->persist($degressif);
                }

                if (  ($p!=2) && ($price > $prevPrixHT) )
                {
                   // $this->issue($i, $output, "The price increased.");
                }
                $prevPrixHT = $price;
                if (  ($p!=2) && ($palier < $prevPalier) )
                {
                    $this->issue($i, $output, "The price starting quantity decreased.");
                }
                $prevPalier = $palier;
                if ($price <= 0.0 )
                {
                    $this->issue($i, $output, "Invalid price.");
                }

            }
            parent::advance($i, $output);
            $i++;
        }

        $this->finish();
        $output->writeln('');
        $output->writeln($skipped.' "degressif" entries skipped.' );
        return $skipped;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $fileName = 'web/uploads/import/' . $this->getRegion() . '/DEGRESSI.CSV';
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert($fileName, ',');

        return $data;
    }
}