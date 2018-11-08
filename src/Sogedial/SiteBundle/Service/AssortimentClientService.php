<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;

class AssortimentClientService
{
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->assortimentClientRepository = $this->em->getRepository('SogedialSiteBundle:AssortimentClient');
    }

    /**
     * @param string codeClient
     * @param string valeur
     * @return boolean true if has changed else false
     */
    public function chooseAssortimentClient($codeClient, $valeur)
    {
        $assortimentsClientCourant = $this->assortimentClientRepository->findOneBy(
            array(
                "client" => $codeClient,
                "assortimentCourant" => true
            )
        );

        $assortimentsClientChoose = $this->assortimentClientRepository->findOneBy(
            array(
                "client" => $codeClient,
                "valeur" => $valeur
            )
        );

        //nothing change
        if ($assortimentsClientChoose->getId() === $assortimentsClientCourant->getId()) {
            return false;
        }

        $assortimentsClientChoose->setAssortimentCourant(true);
        $assortimentsClientCourant->setAssortimentCourant(false);

        $this->em->persist($assortimentsClientCourant);
        $this->em->persist($assortimentsClientChoose);
        $this->em->flush();
        return true;
    }
}