<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Sogedial\SiteBundle\Entity\Client;

class AssortimentClientRepository extends EntityRepository
{
    public function getValeur(Client $client)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ac')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->where('ac.client = :client')
            ->setParameter('client', $client);

        return $qb->getQuery()->getResult();
    }

    public function findByClientSameEnseigne($codeEnseigne)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ac')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->leftJoin('ac.enseigne', 'ens')
            ->setParameter('client', $codeEnseigne);

        return $qb->getQuery()->getResult();
    }

    public function findByClientWithOrder($codeClient)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ac')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->where('ac.client = :client')
            ->orderBy('ac.as400assortiment')
            ->setParameter('client', $codeClient);
        return $qb->getQuery()->getResult();
    }

    public function getCurrentAssortimentByCodeClient($codeClient)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('ac')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->where('ac.client = :client')
            ->andWhere('ac.assortimentCourant = 1')
            ->setParameter('client', $codeClient);
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $valeur
     */
    public function deleteAssortimentByValeur($valeur)
    {
        $Parameters = array(
            'valeur' => $valeur
        );

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('ac')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->leftJoin('Sogedial\SiteBundle\Entity\Assortiment', 'ass', \Doctrine\ORM\Query\Expr\Join::WITH, 'ass.code = ac.assortiment')
            ->where("ac.valeur = :valeur")
            ->setParameters($Parameters);

        $results = $qb->getQuery()->execute();

        foreach ($results as $result) {
            $this->_em->remove($result);
        }

        $this->_em->flush();

    }

    public function getAssortimentsClientSameEnseigne($codeClient, $codeEnseigne)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('c.nom as nomClient, assortiment.valeur as valeurAssortiment, ac.nom as nomAssortiment')
            ->from('SogedialSiteBundle:AssortimentClient', 'ac')
            ->leftJoin('ac.client', 'c')
            ->leftJoin('ac.assortiment', 'assortiment')
            ->where('c.enseigne = :codeEnseigne')
            ->andWhere('ac.as400assortiment != 1')
            ->setParameter('codeEnseigne', $codeEnseigne);
        return $qb->getQuery()->getArrayResult();
    }
}