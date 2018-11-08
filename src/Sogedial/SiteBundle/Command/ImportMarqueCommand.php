<?php

namespace Sogedial\SiteBundle\Command;


use Sogedial\SiteBundle\Entity\Marque;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportMarqueCommand extends ImporterManager
{
    protected function configure()
    {
        parent::configureCmd(
            'sogedial:importMarqueCsv',
            'Import marque from CSV file'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandeName = 'marque';
        $this->executeCmd($this, $commandeName, $input, $output);
    }

    protected function import($data, OutputInterface $output)
    {
        $i = 0;
        $skipped = 0;
        //Specifique à sogedial
        $listeMdd = $this->getContainer()->get('sogedial.export')->getListMarqueMDD();
        $arrayExcluded = array('.', '..', '...');
        
        foreach ($data as $row) {

            if (!in_array($row[0], $arrayExcluded)) {
                $marque = parent::$em->getRepository('SogedialSiteBundle:Marque')->findOneBy(array('code' => $row[0]));
            } else {
                $marque = parent::$em->getRepository('SogedialSiteBundle:Marque')->findOneBy(array('code' => '.'));
            }



            if ( !($marque instanceof Marque)) {
                $marque = new Marque();
                $marque->setCode($row[0]);
            }

            if (in_array($row[0], $arrayExcluded)) {
                $marque->setLibelle(utf8_encode('NON DETERMINE'));
            }
            else{
                $marque->setLibelle(utf8_encode(trim($row[1])));
            }

            if (in_array($row[1], $listeMdd)) {
                $marque->setIsMdd(true);
            } else {
                $marque->setIsMdd(false);
            }
            
            parent::$em->persist($marque);
            parent::advance($i++, $output);
        }
        $this->finish();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $converter = $this->getContainer()->get('sogedial_import.csvtoarray');
        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/PMMARQP.CSV', ',');
        
        if ($data!==false  && $data !== null)
        {
            return $data;
        }

        $data = $converter->convert('web/uploads/import/' . $this->getRegion() . '/MARQUE.CSV', ',');        
        return $data;
    }

    ////////// Partie specifique au fonctionnement de de sogedial anciens site export.com

    /**
     * @return array
     */
    public function getListMarqueMDD()
    {
        return $marqueMDD = array('GRAND JURY',
            'GRAND JURY EQUILIBRE',
            'GRAND JURY PARFUMERIE',
            'GRAND JURY BIO',
            'GRAND JURY PREMIUM',
            'CARREFOUR',
            'CARREFOUR BIO',
            'CARREFOUR KIDS',
            'CARREFOUR SELECTION',
            'CARREFOUR LIGHT',
            'CARREFOUR AGIR',
            'CARREFOUR DISCOUNT',
            'CARREFOUR EXOTIQUE',
            'CARREFOUR BABY',
            'CARREFOUR HOME',
            'CARREFOUR ECOPLA',
            'CARREFOUR CDM',
            'CARREFOUR DISCOUNT',
            'CARREFOUR HALAL',
            'CARREFOUR BON APPETIT',
            'CARREFOUR DISNEY',
            'CARREFOUR SHIELD',
            'RECETTE CARREFOUR',
            'GRAND JURY PARFUMERIE',
            'EN CUISINE',
            'MDD',
            'REFLETS DE France',
            'SAXO',
            'TERRE Italie',
            'HAPPY NUT',
            'MAITRES GOUSTIERS',
            'AUGUSTIN F',
            'Marque Distributeur',
            'COURANCES',
            'DURENMEY',
            'DURENMEYER',
            'ENGHIEN',
            'FLEURY',
            'HONORE',
            'J.LAFONT',
            'KIEFFER',
            'LARMIGNY',
            'LOUIS DE RETZ',
            'PETER HERRES GMBH',
            'BAYANIS',
            'FORTENI',
            'WESTPORT',
            'PÈRE DAMIEN',
            'PROMOCASH',
            'SAINT MERAC',
            'ESTRIBOS',
            'KEN LOUGH',
            'LOCH CASTLE',
            'OLD THAMES',
            'SNIEZKA',
            'VIKOROFF',
            'LES COSMETIQUES',
            'LES COSMETIQUES NECTAR',
            'PRESERVEX',
            'TEX',
            'TEX BABY',
            'BLUESKY',
            'GRAND JURY TOUT PRÊT',
            'EQC',
            'FQC',
            'ORIGINE & QUALITE',
            'SPNP',
            'DESTINATION SAVEURS'
        );
    }
}