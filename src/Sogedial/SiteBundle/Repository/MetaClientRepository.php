<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Sogedial\UserBundle\Repository;

class MetaClientRepository extends EntityRepository
{
     /**
     * @param $code
     * @return array
     */
    public function getMetaFilter($codeClient)
    {
        $codeClientTmp = explode("-", $codeClient);
        $qb = $this->createQueryBuilder('mc');

        $qb->add('select', 'mc.code, mc.libelle, c.ville, ent.raisonSociale, fos.username')
		    ->add('from', 'SogedialSiteBundle:MetaClient mc')
            ->innerJoin('SogedialSiteBundle:Client','c',\Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta IS NOT NULL')
            ->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = c.entreprise')
            ->innerJoin('SogedialUserBundle:User','fos',\Doctrine\ORM\Query\Expr\Join::WITH, 'fos.meta = c.meta')
            ->where('c.code LIKE :codeClient')
            ->andWhere('c.meta = mc.code')
            ->groupBy('mc.code');

        $qb->setParameter('codeClient', '%'.$codeClientTmp[1]);

        $result = $qb->getQuery()->getResult();
        return $result;
    }

    public function generateNewMetaCode() {
        $code;
        do {
            $code = sprintf('%s-%s', 'Ecom', sprintf("%09d", rand()));
            $foundMeta = $this->find($code);
        } while ($foundMeta !== null);
        return $code;
    }
}