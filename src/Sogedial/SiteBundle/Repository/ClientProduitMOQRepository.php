<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ClientProduitMOQRepository
 */
class ClientProduitMOQRepository extends EntityRepository
{
    // To access foreign repositories from within another: https://tools.belchamber.us/php-symfony-accessing-repositories-from-inside-a-repository/
    protected $produitRepository;

    function __construct($em, $class) {
        parent::__construct($em, $class);
        $this->produitRepository = $em->getRepository('SogedialSiteBundle:Produit');
    }

    public function getQMinFromCodeClientAndCodeProduit($codeClient, $codeProduit)
    {
        $qb = $this->createQueryBuilder('c');
        $params = array(
            'codeClient' => $codeClient,
            'codeProduit' => $codeProduit
        );
        $qb->add('select', 'c.quantiteMinimale')
            ->add('from', 'SogedialSiteBundle:ClientProduitMOQ c')
            ->where('c.client = :codeClient')
            ->andWhere('c.produit = :codeProduit')
            ->setParameters($params);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getQMinFromCodeClientAndEan13Produit($codeClient, $ean13Produit)
    {
        $codeProduit = $this->produitRepository->findBy(array("ean13" => $ean13Produit))[0]->getCode();
        return $this->getQMinFromCodeClientAndCodeProduit($codeClient, $codeProduit);
    }
}
