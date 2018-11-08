<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use  Sogedial\SiteBundle\Entity\Produit;
use  Sogedial\SiteBundle\Entity\Client;
use  Sogedial\SiteBundle\Entity\ClientProduitMOQ;

class ClientProduitMOQService
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $repository = "SogedialSiteBundle:ClientProduitMOQ";
        $this->clientProduitMOQRepository = $em->getRepository($repository);
    }

    /**
     * @param Product $product
     * @param Client $client
     * @return mixe
     */
    public function appendOrUpdate(Produit $product, Client $client, $quantity)
    {
        $clientProduitMOQ =  $this->clientProduitMOQRepository->findOneBy([
            "client" => $client->getCode(), 
            "produit" => $product->getCode()
        ]);

        if($clientProduitMOQ === NULL){
            $clientProduitMOQ = new ClientProduitMOQ();
            $clientProduitMOQ->setProduit($product);
            $clientProduitMOQ->setClient($client);            
        }

        $clientProduitMOQ->setQuantiteMinimale($quantity);

        $this->em->persist($clientProduitMOQ);
        $this->em->flush();

        return $clientProduitMOQ;
    }
}