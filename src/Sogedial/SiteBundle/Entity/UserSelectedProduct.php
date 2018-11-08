<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sogedial\SiteBundle\Entity\UserSelectedProduct
 *
 * @ORM\Table(name="user_selectedproduct")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\UserSelectedProductRepository")
 */
class UserSelectedProduct
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")
     */
    private $user;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserSelectedProduct
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set produit
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produit
     * @return UserSelectedProduct
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit)
    {
        $this->produit = $produit;

        return $this;
    }

    /**
     * Get produit
     *
     * @return \Sogedial\SiteBundle\Entity\Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }

    /**
     * Set user
     *
     * @param \Sogedial\UserBundle\Entity\User $user
     * @return UserSelectedProduct
     */
    public function setUser(\Sogedial\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Sogedial\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
