<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Constraints\DateTime;
use Sogedial\UserBundle\Entity\User;

class ClientRepository extends EntityRepository
{
    /**
     * @param $code
     * @return array
     */
    public function getClientInformation($code)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c.nom, ens.libelle as enseigneClient, c.code')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->leftJoin('c.enseigne', 'ens')
            ->where('c.code = :clientCode');

        $qb->setParameter('clientCode', $code);

        $result = $qb->getQuery()->getResult();

        return current($result);
    }

    /**
     * @param $codeClient
     * @return array
     */
    public function getClientByCode($codeClient)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->where('c.code = :clientCode');

        $qb->setParameter('clientCode', $codeClient);

        $result = $qb->getQuery()->getResult();
        return (count($result) > 0 ? $result[0] : null);
    }

    /**
     * Get all client
     *
     * @return array
     */
    public function getListClients()
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c')
            ->add('from', 'SogedialSiteBundle:Client c');
        $results = $qb->getQuery()->getArrayResult();

        return $results;

    }

    public function getClientFromUser(User $user)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->add('select', 'c')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->innerJoin('SogedialUserBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta')
            ->where('u.id = :user_id')
            ->setParameter("user_id", $user->getId());

        return $qb->getQuery()->getSingleResult();
    }

    public function getUserFromClientId($code_client)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->add('select', 'u')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->innerJoin('SogedialUserBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta')
            ->where('c.code = :client')
            ->setParameter("client", $code_client);

        return $qb->getQuery()->getSingleResult();
    }

    public function getActiveClientsByCodeEntreprise($code_entreprise)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('c.nom,c.code, ens.libelle as enseigne')
            ->from('SogedialSiteBundle:Client', 'c')
            ->leftJoin('c.enseigne', 'ens')
            ->where('c.entreprise = :codeEntreprise')
            ->andWhere("c.e_actif = 1 ")
            ->andWhere("c.is_prospect = 0 ")
            ->setParameter('codeEntreprise', $code_entreprise);
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get all client
     *
     * @return array
     */
    public function getListClients2($status, $page, $code_entreprise = false, $query = false, $isProspect = false)
    {
        $limit = 10;
        $limit1 = ($page - 1) * $limit;
        $limit2 = $limit;

        if ($limit1 < 0) {
            $limit1 = 0;
        }

        $qb = $this->createQueryBuilder('c');
        $qb->select('c.code, c.nom, u.username, u.dateFinValidite, count(DISTINCT s.id) as nbConnexion, c.statut, c.email, ent.code as entreprise, IDENTITY(c.tarification) as codeTarification, ens.libelle as libelleEnseigne, (c.meta) as has_meta, c.e_actif as enabled, ent.nomEnvironnement')

            ->add("from", "SogedialSiteBundle:Client c");

            // A rajouter / optimiser pour obtenir le nombre de produits dans l'assortiment prospect
            // count(DISTINCT a.produit) as nbProducts, 

            //Get assortiment products number
            // ->leftJoin('SogedialSiteBundle:AssortimentClient', 'assort', 'WITH', "assort.client=c.code")
            // ->leftJoin('SogedialSiteBundle:Assortiment', 'a', 'WITH', "a.valeur=assort.valeur")
            // ->where('a.entreprise = c.entreprise')
            // ->andWhere('a.valeur = assort.valeur')
            // ->orderBy("c.id");

            //Get only the products number for default assort in case of client (prevent multiple assortiments)
            // if (!$isProspect){
            //     $qb->andWhere('assort.as400assortiment = 1');
            // }
            $qb->groupBy("c.code");

            




        if ($code_entreprise !== false) {
            $qb->innerJoin('SogedialSiteBundle:Entreprise', 'ent', \Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = :entreprise AND ent = c.entreprise');
        } else {
            $qb->leftJoin('SogedialSiteBundle:Entreprise', 'ent', \Doctrine\ORM\Query\Expr\Join::WITH, 'ent = c.entreprise');
        }
        $qb->leftJoin('SogedialSiteBundle:Entreprise', 'ent2', \Doctrine\ORM\Query\Expr\Join::WITH, 'ent2 = ent.entrepriseParent');

        $qb->leftJoin('c.enseigne', 'ens')
            ->leftJoin('SogedialUserBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.meta = u.meta');

        //Get user connexions
        $qb->leftJoin("SogedialSiteBundle:Session", 's', 'WITH', "s.user = u.id");
        // ->groupBy("u.id");
        

        // TODO revenir dessus lorsque la story multi compte fos_user sera plus clair
        // ->where(
        //     $qb->expr()->orX(
        //         $qb->expr()->andX(
        //             $qb->expr()->isNotNull('c.meta'),
        //             $qb->expr()->eq('u.entreprise', 'c.entreprise'),
        //             $qb->expr()->isNull('u.preCommande')
        //         ),
        //         $qb->expr()->andX(
        //             $qb->expr()->isNotNull('c.meta'),
        //             $qb->expr()->eq('ent2', 'u.entreprise'),
        //             $qb->expr()->eq('ent', 'c.entreprise'),
        //             $qb->expr()->eq('ent2', 'ent.entrepriseParent'),
        //             $qb->expr()->isNotNull('u.preCommande')
        //         )
        //     )
        // );

        if ($status === 'ACTIF') {
            $qb->andWhere('c.e_actif = 1');
            $qb->andWhere('u.meta IS NOT NULL');
        } elseif ($status === 'BLOQUE') {
            $qb->Where('c.e_actif = 0');
            $qb->andWhere('u.meta IS NOT NULL');
        } elseif ($status === 'SANSLOGIN') {
            $qb->andWhere('u.meta IS NULL');
        } elseif ($status === "SEARCH") {
            $qb->Where('c.nom LIKE :query');
            $qb->orWhere('c.code LIKE :query');
            $qb->setParameter("query", "%" . $query . "%");
        }
        $qb->andWhere('c.is_prospect = :prospect');
        $qb->setParameter("prospect", $isProspect);

        $qb->orderBy('c.enseigne', 'ASC')
            ->setFirstResult($limit1)
            ->setMaxResults($limit2);
        if ($code_entreprise !== false) {
            $qb->setParameter("entreprise", $code_entreprise);
        }
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get count actif customer
     *
     * @return number
     */
    public function countClientsActif()
    {

        $results = 5;
        return $results;
    }

    public function countClientsInactif()
    {
        //$qb = $this->createQueryBuilder();
        /**$qb->select('u')
         * ->from('SogedialSiteBundle:User', 'u')
         * ->join('u.Code', 'c')
         * ->Where('u.enabled=1');
         *
         *
         * $results = $qb->getQuery()->getResult();**/
        $results = 15;
        return $results;
    }

    public function countClientsBloques()
    {
        /**$qb = $this->createQueryBuilder('c, u')
         * ->select('COUNT(u)')
         * ->from('SogedialSiteBundle:User', 'u')
         * ->join('u.Code', 'c')
         * ->Where('u.enabled=0');
         * $results = $qb->getQuery()->getSingleResult();**/
        $results = 25;
        return $results;
    }

    /**
     * Get all clients with their tarif
     *
     * @return array
     */
    public function getListClientsWithTarification()
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c, tar')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->join('c.tarification', 'tar');
        $results = $qb->getQuery()->getArrayResult();

        return $results;

    }

    /**
     * Get all clients with their tarif
     *
     * @return array
     */
    public function getListClientsFromNeedle($needle, $code_entreprise = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->add('select', 'c.nom as text');
        $qb->add('from', 'SogedialSiteBundle:Client c');
        if ($code_entreprise !== false) {
            $qb->innerJoin('SogedialSiteBundle:Entreprise', 'ent', \Doctrine\ORM\Query\Expr\Join::WITH, 'ent.code = ' . $code_entreprise . ' AND ent = c.entreprise');
        }
        $qb->where("c.nom LIKE :nom")
            ->orWhere("c.code LIKE :nom")
            ->setParameter("nom", "%" . $needle . "%");
        $results = $qb->getQuery()->getArrayResult();

        return $results;

    }

    /**
     * @param $id
     * @return mixed|string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientById($id)
    {

        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c.id, c.code, c.nom, c.dateDebutValidite, c.responsable1, c.responsable2, c.adresse, c.complementAdresse, c.codePostale, c.ville, c.telephone, c.fax, c.email, c.statut')
            ->add('from', 'SogedialSiteBundle:Client c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NoResultException $e) {
            return ($e->getMessage());
        }

    }

    /**
     * Get next delivery date for a customer
     *
     * @return date
     */
    public function getNextDeliveryDate($ambient, $positiveCold, $negativeCold)
    {
        $temperatureDeliveryDate = array();
        $curtime = date('His');
        if ($curtime < 1000) {
            $nextDate = date('Y-m-d', strtotime(date("Y-m-d") . " +2 weekdays"));
        } else {
            $nextDate = date('Y-m-d', strtotime(date("Y-m-d") . " +3 weekdays"));
        }


        if ($ambient) {
            $temperatureDeliveryDate["ambient"] = $nextDate;
        }
        if ($positiveCold) {
            $temperatureDeliveryDate["positiveCold"] = $nextDate;
        }

        if ($negativeCold) {
            $temperatureDeliveryDate["negativeCold"] = $nextDate;
        }

        return $temperatureDeliveryDate;

    }


    /**
     * Create new code for a customer
     *
     * @return date
     */
    public function generateNewProspectCode($societe)
    {
        do {
            $rand = rand(100000, 999999);
            $code = $societe . "-PRP" . $rand;
            $client = $this->getClientByCode($code);
        } while ($client != null);
        return $code;
    }

    public function getFirstClientFromEnseigneAndEntreprise($codeEnseigne, $codeEntreprise)
    {
        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select('c')
            ->from('SogedialSiteBundle:Client', 'c')
            ->where('c.enseigne = :codeEnseigne')
            ->andWhere('c.entreprise = :codeEntreprise')
            ->andWhere('c.is_prospect != 1')
            ->setMaxResults(1)
            ->setParameter('codeEnseigne', $codeEnseigne)
            ->setParameter('codeEntreprise', $codeEntreprise)
            ->getQuery()
            ->getSingleResult();
        return $result;
    }

    public function getClientObject($meta, $entrepriseCoutante)
    {
        $clientParams = array(
            'meta' => $meta,
            'entrepriseCoutante' => $entrepriseCoutante
        );

        $qb = $this->createQueryBuilder('cl')
            ->andWhere('cl.meta = :meta')
            ->andWhere('cl.entreprise = :entrepriseCoutante')
        ;

        $qb->setParameters($clientParams);

        return    $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get codeEnseigne from a customer code
     *
     * @return Array codeEnseigne
     */
    public function getEnseigneByCodeProspect($codeProspect)
    {
        $clientParams = array(
            'codeProspect' => $codeProspect
        );

        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select('ens.code')
            ->from('SogedialSiteBundle:Client', 'c')
            ->leftJoin('c.enseigne', 'ens')
            ->where('c.code = :codeProspect')
        ;

        $qb->setParameters($clientParams);

        return    current($qb->getQuery()->getArrayResult());
    }

    /**
     * Get customers which have same "enseigne", not current prospect and from same "entreprise"
     *
     * @return Array prospects
     */
    public function getProspectsWithEnseigne($codeEntreprise, $codeEnseigne, $codeProspect)
    {
        $clientParams = array(
            'codeEntreprise' => $codeEntreprise,
            'codeEnseigne' => $codeEnseigne,
            'codeProspect' => $codeProspect
        );

        $qb = $this->_em->createQueryBuilder();
        $result = $qb
            ->select('c.code, c.nom')
            ->from('SogedialSiteBundle:Client', 'c')
            ->where('c.enseigne = :codeEnseigne')
            ->andWhere('c.entreprise = :codeEntreprise')
            ->andWhere('c.is_prospect = 1')
            ->andWhere('c.code != :codeProspect')
        ;

        $qb->setParameters($clientParams);

        return    $qb->getQuery()->getArrayResult();
    }
}