<?php

namespace Sogedial\SiteBundle\Service;

class Order extends AbstractService
{
    /**
     * Get the current order for the user
     *
     * @param Sogedial\UserBundle\Entity\User
     * @return Sogedial\SiteBundle\Entity\Order
     */
    public function getCurrentByUser($user)
    {
        if (null === $user) {
            throw new \Sogedial\SiteBundle\Exception\FunctionalException('L\'utilisateur ne peut être null.');
        }
        return $this->getRepository()->getCurrentOrderByUser($user, $user->getEntrepriseCourante());
    }
}

