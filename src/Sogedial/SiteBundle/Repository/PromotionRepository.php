<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Enseigne;

class PromotionRepository extends EntityRepository
{
    public function getPromotionClientByProduct(Produit $product, Client $client)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('pr')
            ->from('SogedialSiteBundle:Promotion', 'pr')
            ->innerJoin("SogedialSiteBundle:Produit", "p", 
                \Doctrine\ORM\Query\Expr\Join::WITH, "p.code = pr.produit")
            ->innerJoin("SogedialSiteBundle:Client", "cl", 
                \Doctrine\ORM\Query\Expr\Join::WITH, "cl = pr.client")
            ->where('p.code = :code_product')
            ->andWhere("cl.code = :code_client")
            ->setParameter('code_product', $product->getCode())
            ->setParameter("code_client", $client->getCode());

        return $qb->getQuery()->getResult();
    }

    public function getPromotionEnseigneByProduct(Produit $product, Enseigne $enseigne)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('pr')
            ->from('SogedialSiteBundle:Promotion', 'pr')
            ->innerJoin("SogedialSiteBundle:Produit", "p", 
                \Doctrine\ORM\Query\Expr\Join::WITH, "p.code = pr.produit")
            ->innerJoin("SogedialSiteBundle:Enseigne", "ens", 
                \Doctrine\ORM\Query\Expr\Join::WITH, "ens = pr.enseigne")
            ->where('p.code = :code_product')
            ->andWhere("ens.code = :code_enseigne")
            ->setParameter('code_product', $product->getCode())
            ->setParameter("code_enseigne", $enseigne->getCode());

        return $qb->getQuery()->getResult();
    }

    public function getPromotionByProduct(Produit $product, Client $client, Enseigne $enseigne)
    {
        $promotionClient = current($this->getPromotionClientByProduct($product, $client));
        if($promotionClient === NULL){
           return current($this->getPromotionEnseigneByProduct($product, $enseigne));
        }

        return $promotionClient;
    }

    public function getPromotionsWithStockEngagementDemandOnActifsClients(){
        $qb = $this->_em->createQueryBuilder();

        $qb->select('pr')
           ->from('SogedialSiteBundle:Promotion', 'pr')
           ->innerJoin("SogedialSiteBundle:Client", "c", 
               \Doctrine\ORM\Query\Expr\Join::WITH, "c = pr.client AND c.e_actif = 1")
           ->where('pr.stockEngagementDemande IS NOT NULL')
           ->andWhere("pr.codeTypePromo = 'EF'");

        return $qb->getQuery()->getResult();
    }

    public function resetStockEngagementDemand()
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->update('SogedialSiteBundle:Promotion', 'pr')
            ->set('pr.stockEngagementDemande', "NULL")
            ->where('pr.stockEngagementDemande IS NOT NULL')
            ->andWhere("pr.codeTypePromo = 'EF'");

        return $qb->getQuery()->execute();
    }

    /**
     * @param $codeClient
     * @param $productCode
     * @return mixed
     */
    public function getPromotionByCodeClientAndProductCode($codeClient, $productCode)
    {
        $prParams = array(
            'codeClient' => $codeClient,
            'productCode' => $productCode
        );

        $qb = $this->createQueryBuilder('pr')
            ->andWhere('pr.client = :codeClient')
            ->andWhere('pr.produit = :productCode');

        $qb->setParameters($prParams);


        return $qb->getQuery()->getOneOrNullResult();
    }
}
