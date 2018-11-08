<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\DateTime;
use Sogedial\UserBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\MessageClient;

class MessageClientRepository extends EntityRepository
{

   public function getMessagesClientsByCodeEntreprise($codeEntreprise)
   {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('mc')
            ->from('SogedialSiteBundle:MessageClient', 'mc')
            ->leftJoin('mc.entreprise', 'entreprise')
            ->where("entreprise = :codeEntreprise")
            ->setParameter('codeEntreprise',$codeEntreprise);

        return $qb->getQuery()->execute();
   }

   public function editMessageById($messageId, $messageLibelle, $messageText, $messageDateDebut, $messageDateFin)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->update('SogedialSiteBundle:MessageClient', 'mc')
            ->set('mc.libelle', ":libelle")
            ->set('mc.text', ":text")
            ->set('mc.dateDebutValidite', ":dateDebut")
            ->set('mc.dateFinValidite', ":dateFin")
            // ->set('mc.e_actif', ":e_actif")
            ->where("mc.id = :id")
            ->setParameter('id', $messageId)
            ->setParameter('libelle', $messageLibelle)
            ->setParameter('text', $messageText)
            ->setParameter('dateDebut', $messageDateDebut)
            ->setParameter('dateFin', $messageDateFin);
            // ->setParameter('e_actif',$message['e_actif']);

        return $qb->getQuery()->getArrayResult();
    }

    // public function createMessageClient($messageLibelle, $messageText, $messageDateDebut, $messageDateFin, $listClients){
    //     $qb = $this->_em->createQueryBuilder();
    //     $request = [];

    //     $messageCode = uniqid("",true);

    //     for ($i=0; $i < count($listClients) - 1; $i++) { 
    //         $request[$i] = array(
    //             'code' => $messageCode,
    //             'client' => $listClients[$i]['code'],
    //             'libelle' => $messageLibelle,
    //             'text' => $messageText,
    //             'dateDebutValidite' => $messageDateDebut,
    //             'dateFinValidite' => $messageDateFin
    //         );
    //     }


    //     $qb->insert('SogedialSiteBundle:MessageClient', 'mc')
    //         ->values($request);

    //     return $qb->getQuery()->execute();
    // }

    // public function getCurrentMessageClients($codeClient)
    // {
    //     $today = new \DateTime('now');
    //     $qb = $this->_em->createQueryBuilder();

    //     $qb->select('mc')
    //         ->from('SogedialSiteBundle:MessageClient', 'mc')
    //         ->leftJoin('mc.clients', 'clients')
    //         ->where("client = :codeClient")
    //         ->andWhere("mc.dateDebutValidite <= :today")
    //         ->andWhere("mc.dateFinValidite >= :today")
    //         ->setParameter('codeClient',$codeClient)
    //         ->setParameter('today',$today);

    //     return $qb->getQuery()->execute();
    // }
}