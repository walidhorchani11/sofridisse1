<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Colis;

class ColisService
{

    private $ms;
    private $em;
    private $sql;

    public function __construct(MultiSiteService $ms, EntityManager $em, SimpleMySQLService $sql)
    {
        $this->ms = $ms;
        $this->em = $em;
        $this->sql = $sql;
    }

    public function getWeightAndVolumeColis ($product)
    {
        $colisInfo = $this->em->getRepository('SogedialSiteBundle:Colis')->findOneBy(array('produit' => $product));

        $weightColis = 0; 
        $volumeColis = 0;

        if ($colisInfo instanceof Colis) {
            $weightColis = $colisInfo->getPoidsBrutColis();
            $volumeColis = $colisInfo->getVolumeColis();
        }

        $result['weight'] = $weightColis;
        $result['volume'] = $volumeColis;

        return $result;
    }

    
    public function getVolumeWeightItemTotal($produit, $qty){

        $weightVolumeUnitaire = $this->getWeightAndVolumeColis($produit);

        $result['weightTotal'] = round ($qty * $weightVolumeUnitaire['weight'], 2);
        $result['volumeTotal'] = round ($qty * $weightVolumeUnitaire['volume'], 4);
        

        return $result;
    }
    
}
