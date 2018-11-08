<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class AssortimentRepository extends EntityRepository
{
    /**
     * @param $valeur
     * @param $entreprise
     */
    public function deleteMultipleAssortimentsByValeurAndEntreprise($valeur, $entreprise){
        $Parameters = array(
            'entreprise' => $entreprise,
            'valeur' => $valeur
        );

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('ass')
            ->from('SogedialSiteBundle:Assortiment', 'ass')
            ->leftJoin('Sogedial\SiteBundle\Entity\Entreprise', 'en', \Doctrine\ORM\Query\Expr\Join::WITH, 'en.code = ass.entreprise')
            ->where("ass.entreprise = :entreprise")
            ->andWhere("ass.valeur = :valeur")
            ->setParameters($Parameters);

        $results = $qb->getQuery()->execute();

        foreach ($results as $result) {
            $this->_em->remove($result);
        }

        $this->_em->flush();

    }

    public function getAllProductsNestingFromValeurAndEntreprise($valeur, $codeEntreprise, $codeEnseigne) {
        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select(array(
                'a.code as code_assortiment',
                'sec.code as code_secteur',
                'sec.libelle as libelle_secteur',
                'ray.code as code_rayon',
                'ray.libelle as libelle_rayon',
                'fam.code as code_famille',
                'fam.libelle as libelle_famille',
                'p.code as code_produit',
                'p.denominationProduitBase as libelle_produit',
                'p.marketingCode',
                'm.libelle as marque',
                't.prixHt as prixHt'
            ))
            ->from('SogedialSiteBundle:Assortiment', 'a')
            ->leftJoin('a.produit','p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.secteur', 'sec')
            ->leftJoin('p.rayon', 'ray')
            ->leftJoin('p.famille', 'fam')
            ->leftJoin('p.tarifs', 't')
            ->where('p.actif = 1')
            ->andWhere('a.valeur = :valeur')
            ->andWhere('a.entreprise = :codeEntreprise')
            ->andWhere('t.enseigne = :codeEnseigne')
            ->setParameter('valeur', $valeur)
            ->setParameter('codeEntreprise', $codeEntreprise)
            ->setParameter('codeEnseigne', $codeEnseigne)
            ->orderBy('sec.libelle', 'ASC')
            ->addOrderBy('ray.libelle', 'ASC')
            ->addOrderBy('fam.libelle', 'ASC')
            ->addOrderBy('p.denominationProduitBase', 'ASC')
            ->addOrderBy('t.prixHt', 'ASC')
            ->getQuery()
            ->getArrayResult();
        return $result;
    }

    public function getAllProductCodesFromValeurAndEntreprise($codeEntreprise, $valeur) {
        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select(array(
                'a.code as code_assortiment',
                'p.code as code_produit',
            ))
            ->from('SogedialSiteBundle:Assortiment', 'a')
            ->where('a.valeur = :valeur')
            ->andWhere('a.entreprise = :codeEntreprise')
            ->leftJoin('a.produit','p')
            ->setParameter('valeur', $valeur)
            ->setParameter('codeEntreprise', $codeEntreprise)
            ->getQuery()
            ->getArrayResult();
        return $result;
    }

    public function getAllValeursQueryBuilderForRegion($region) {
        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select('a')
            ->from('SogedialSiteBundle:Assortiment', 'a')
            ->where('a.region = :region')
            ->setParameter('region', $region)
            ->groupBy('a.valeur');
        return $qb;
    }

    public function getProductNumberFromValeurAndEntreprise($codeClient,$codeEntreprise,$isProspect){
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COUNT(DISTINCT code_produit)')
           ->from('SogedialSiteBundle:Assortiment', 'a')
           ->leftJoin('SogedialSiteBundle:AssortimentClient', 'as', 'WITH', "as.valeur=a.valeur")
           ->where('as.code_client = :codeClient')
           ->where('a.codeEntreprise = :codeEntreprise')
           ->andWhere('a.valeur = :valeur')
           ->setParameter('codeClient',$codeClient)
           ->setParameter('codeEntreprise',$codeEntreprise);

        if (!$isProspect){
            $qb->andWhere('as.as400_assortiment = 1');
        }
        $result = $qb->getResult();
        
        return $result;
    }
}