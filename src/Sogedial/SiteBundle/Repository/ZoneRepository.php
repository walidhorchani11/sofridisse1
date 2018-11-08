<?php
namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ZoneRepository extends EntityRepository
{
    public function getListZones($codeEntreprise)
    {
        $qb = $this->createQueryBuilder('z');
        $parameters = array("entreprise" => $codeEntreprise);

        $qb->add('select', 'z')
            ->add('from', 'SogedialSiteBundle:Zone z');
        if($codeEntreprise !== false){
            $qb->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = z.entreprise')
                ->setParameters($parameters);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getListZonesByEntreprise($codeEntreprise, $temperature){
        $qb = $this->createQueryBuilder('z');
        $parameters = array(
            "entreprise" => $codeEntreprise,
            "temperature" => $temperature
            );

        $qb->add('select', 'z')
            ->add('from', 'SogedialSiteBundle:Zone z')
            ->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = z.entreprise')
            ->where("z.temperature = :temperature");

        $qb->setParameters($parameters);

        return $qb;
    }
}