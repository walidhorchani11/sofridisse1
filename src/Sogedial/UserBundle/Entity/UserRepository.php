<?php

namespace Sogedial\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{

    public function findAllUserJson()
    {
        return $this->getEntityManager()
            ->createQuery(
                '
        SELECT u
        FROM SogedialUserBundle:User u
        ORDER BY u.username ASC'
            )
            ->getResult();
    }

    public function getQueryListCommercials(){
        $qb = $this->createQueryBuilder('u');
        $parameters = array();

        $qb->add('select', 'u')
            ->add('from', 'SogedialUserBundle:User u')
            ->where('u.meta IS NOT NULL')
            ->andWhere('u.entreprise IS NOT NULL');

        return $qb;
    }


    public function getListClient()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->add('select', 'c.code, c.nom, c.statut, c.email, ens.libelle as libelleEnseigne')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->leftJoin('c.enseigne', 'ens')
            ->orderBy('c.nom', 'ASC');

        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    public function getNumberOfClients($code_entreprise = false)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'count(c.code) as nbrClients')
            ->add('from', 'SogedialSiteBundle:Client c');
        if($code_entreprise !== false){
            $qb->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = c.entreprise');
        }
        $qb->orderBy('c.nom', 'ASC');

        if($code_entreprise !== false){
            $qb->setParameters(["entreprise" => $code_entreprise]);
        }

        return $qb->getQuery()->getSingleResult();
    }

    public function getListClientWithAccess()
    {
        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'c.code, c.nom, c.statut, c.email, ens.libelle as libelleEnseigne, u.enabled as statutUser')
            ->add('from', 'SogedialUserBundle:User u, SogedialSiteBundle:Client c')
            ->leftJoin('c.enseigne', 'ens')
            ->where('u.meta = c.meta')
            ->andWhere('u.meta IS NOT NULL')
            ->andWhere('c.e_actif = TRUE');

        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    public function getNumberOfClientsWithAccess($code_entreprise = false, $getProspects = false)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'count(client.code) as nbrClientsWithAccess')
            ->add('from', 'SogedialSiteBundle:Client client')
            //->innerJoin('SogedialUserBundle:User','user',\Doctrine\ORM\Query\Expr\Join::WITH, 'user.client = client AND user.enabled = 1')
            ->where('client.meta IS NOT NULL')
            ->andWhere('client.e_actif = TRUE')
            ->andWhere('client.is_prospect = :getProspects')
            ->setParameter('getProspects', $getProspects);

            if($code_entreprise !== false){
            $qb->innerJoin('SogedialSiteBundle:Entreprise','entreprise',\Doctrine\ORM\Query\Expr\Join::WITH, 'entreprise = client.entreprise')
                ->andWhere('entreprise.code = :entreprise')
                ->setParameter("entreprise", $code_entreprise);
        }

        return $qb->getQuery()->getSingleResult();
    }

    public function getNumberOfClientsWithoutAccess($code_entreprise = false)
    {
        $userQb = $this->createQueryBuilder('u');

        $userQb->add('select', 'COUNT(c.code) as nbrClientsWithoutAccess')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->where('c.meta IS NULL');
        if($code_entreprise !== false){
            $userQb->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = c.entreprise');
            $userQb->setParameter("entreprise", $code_entreprise);
        }

        return $userQb->getQuery()->getSingleResult();
    }

    public function numberOflockedClients($code_entreprise = false, $getProspects = false)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'count(c.code) as nbrClientsLocked')
            ->add('from', 'SogedialUserBundle:User u')
            ->innerJoin('SogedialSiteBundle:Client','c',\Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta')
            ->where('c.e_actif = 0')
            ->andWhere('u.meta = c.meta')
            ->andWhere('c.meta IS NOT NULL')
            ->andWhere('c.is_prospect = :getProspects')
            ->setParameter('getProspects', $getProspects);

        if($code_entreprise !== false){
            $qb->innerJoin('SogedialSiteBundle:Entreprise','ent',\Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = c.entreprise')
                ->setParameter("entreprise", $code_entreprise);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array
     */
    public function getTrackedUsers($code_entreprise = false,$page = 1)
    {
        $limit = 10;
        $offset = $page * $limit;
        if($code_entreprise !== false){

        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'u')
            ->add('from', 'SogedialUserBundle:User u')
            ->leftJoin('Sogedial\SiteBundle\Entity\Client', 'c',
                \Doctrine\ORM\Query\Expr\Join::WITH, 'u.meta = c.meta')
            ->where('u.enabled = 1')
            ->andWhere('c.entreprise = :entreprise')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $qb->setParameter('entreprise', $code_entreprise);

        }

        $result = $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        return $result;
    }

    public function getNoCommercialFosUsers($precommande = false)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id as code, c.nom as nom')
            ->innerJoin('SogedialSiteBundle:Client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta and u.entrepriseCourante = c.entreprise')
            ->where('u.meta IS NOT NULL');
        if($precommande){
            $qb->andWhere('u.preCommande IS NOT NULL');
        }

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * @return mixed
     */
    public function getCommercialInformation($societe)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'u')
            ->add('from', 'SogedialUserBundle:User u')
            ->where('u.enabled = 1')
            ->andWhere('u.meta IS NULL')
            ->andWhere('u.entreprise = :entreprise')
            ->setParameter('entreprise', $societe);

        return current($qb->getQuery()->getArrayResult());
    }

    /**
     * @param $userId
     * @return array
     */
    public function getClientInformation($userId, $codeEntreprise = "")
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('c.nom, c.code, c.isClientMeti')
            ->addSelect("u.username, u.enabled, u.entrepriseCourante")
            ->addSelect("ens.libelle as enseigneClient, ens.code as enseigneClientCode")
            ->addSelect("tar.code as tarificationClientCode")

            ->leftJoin('SogedialSiteBundle:Client', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta')
            ->leftJoin('c.enseigne', 'ens')
            ->leftJoin('c.tarification', 'tar')

            ->where('u.id = :userId')
            //->andWhere('u.enabled = 1')
            ->andWhere('u.meta IS NOT NULL');

        $qb->setParameter('userId', $userId);

        if($codeEntreprise !== ""){
            $qb->innerJoin('SogedialSiteBundle:Entreprise', 'ent', \Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :code_entreprise');
            $qb->setParameter("code_entreprise", $codeEntreprise);
        } else {
            $qb->leftJoin('SogedialSiteBundle:Entreprise', 'ent', \Doctrine\ORM\Query\Expr\Join::WITH, 'u.entrepriseCourante = ent.code');
        }
        $qb->andWhere("u.entrepriseCourante = ent.code");

        $result = $qb->getQuery()->getResult();
        return current($result);
    }

    /**
     * @param $client
     * @return array
     */
    public function getClientInformation2($client)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->add('select', 'c.nom, u.username, ens.libelle as enseigneClient, c.code, u.enabled, ens.code as enseigneClientCode, tar.code as tarificationClientCode')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->leftJoin('SogedialUserBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta')
            ->leftJoin('c.enseigne', 'ens')
            ->leftJoin('c.tarification', 'tar')
            ->where('c.code = :client');

        if (is_object($client)) $client = $client->getClient()->getCode();

        $qb->setParameter('client', $client);

        $result = $qb->getQuery()->getResult();
        return current($result);
    }

    /**
     * @return mixed
     */
    public function updateUserCcvStatus()
    {
        $today = new \DateTime('now');

        $qb = $this->createQueryBuilder('u');

        $q = $qb->update('SogedialUserBundle:User', 'u')
            ->set('u.alreadySigned', '?1')
            ->set('u.cgvCpvUpdatedAt', '?2')
            ->set('u.cgvCpvSignedAt', '?3')
            ->where('u.cgvCpv = 1')
            ->andWhere("u.etat = 'client' ")
            ->setParameter(1, 0)
            ->setParameter(2, $today)
            ->setParameter(3, null)
            ->getQuery();

        return $q->execute();
    }

    /**
     * @param $validatorId
     * @return array
     */
    public function getUserFromOrderValidatorId($validatorId)
    {
        $qb = $this->createQueryBuilder('u, m');

        $qb->add('select', 'u')
            ->add('from', 'SogedialUserBundle:User u')
            ->leftJoin('u.meta', 'm')
            ->where('u.id = :validatorId')
            ->setParameter('validatorId', intval($validatorId))
            ->setMaxResults(1)
        ;

        return current($qb->getQuery()->getResult(Query::HYDRATE_OBJECT));
    }


}
