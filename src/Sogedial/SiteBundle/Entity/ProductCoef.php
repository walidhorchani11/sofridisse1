<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sogedial\SiteBundle\Entity\ProductCoef
 *
 * @ORM\Table(name="produit_coef")
 * @ORM\Entity()
 */
class ProductCoef
{

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="productCoef", cascade={"persist"})
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $product;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_price", type="boolean", options={"default":0})
     */
    private $show_price;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_promotion", type="boolean", options={"default":0})
     */
    private $show_promotion;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_exclusivity", type="boolean", options={"default":0})
     */
    private $show_exclusivity;

    /**
     * @var decimal
     *
     * @ORM\Column(name="total_price", type="decimal", nullable=true, scale=2)
     */
    private $totalPrice;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_new", type="boolean", options={"default":0})
     */
    private $is_new;

    /**
     * Set user
     *
     * @param User $user
     * @return ProductSelection
     */
    public function setUser(\Sogedial\UserBundle\Entity\User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set show_price
     *
     * @param boolean $showPrice
     * @return ProductSelection
     */
    public function setShowPrice($showPrice)
    {
        $this->show_price = $showPrice;

        return $this;
    }

    /**
     * Get show_price
     *
     * @return boolean
     */
    public function getShowPrice()
    {
        return $this->show_price;
    }

    /**
     * Set show_promotion
     *
     * @param boolean $showPromotion
     * @return ProductSelection
     */
    public function setShowPromotion($showPromotion)
    {
        $this->show_promotion = $showPromotion;

        return $this;
    }

    /**
     * Get show_promotion
     *
     * @return boolean
     */
    public function getShowPromotion()
    {
        return $this->show_promotion;
    }

    /**
     * Set show_exclusivity
     *
     * @param boolean $showExclusivity
     * @return ProductSelection
     */
    public function setShowExclusivity($showExclusivity)
    {
        $this->show_exclusivity = $showExclusivity;

        return $this;
    }

    /**
     * Get show_exclusivity
     *
     * @return boolean
     */
    public function getShowExclusivity()
    {
        return $this->show_exclusivity;
    }

    /**
     * Set totalPrice
     *
     * @param string $value
     * @return ProductSelection
     */
    public function setTotalPrice($value)
    {
        $this->totalPrice = $value;

        return $this;
    }

    /**
     * Get total price
     *
     * @return string
     */
    public function getTotalprice()
    {
        return $this->totalPrice;
    }

    /**
     * Set is_new
     *
     * @param boolean $isNew
     * @return ProductSelection
     */
    public function setIsNew($isNew)
    {
        $this->is_new = $isNew;

        return $this;
    }

    /**
     * Get is_new
     *
     * @return boolean
     */
    public function getIsNew()
    {
        return $this->is_new;
    }

    /**
     * Set entity
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $entity
     * @return ProductSelection
     */
    public function setProduct(\Sogedial\SiteBundle\Entity\Produit $entity)
    {
        $this->produit = $entity;
        return $this;
    }

    /**
     * Get entity
     *
     * @return \Sogedial\SiteBundle\Entity\Produit
     */
    public function getProduct()
    {
        return $this->produit;
    }

}
