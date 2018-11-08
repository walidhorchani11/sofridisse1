<?php

namespace Sogedial\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sogedial\UserBundle\Entity\User;

/**
 * Groupe
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Sogedial\UserBundle\Entity\ProductSelectionRepository") 
 * @ORM\Table(name="user_product_selections")
 */
class ProductSelection {

  public $checked = true;
  public $entity_id = '';
  public $user_id = '';

  /**
   * @ORM\Id
   * @ORM\ManyToOne(targetEntity="User", inversedBy="product_selections", cascade={"persist"})
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")
   */
  private $user;

  /**
   * @ORM\Id
   * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="selections", cascade={"persist"})
   * @ORM\JoinColumn(name="entity", referencedColumnName="code_produit")
   */
  private $entity;

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
   * @ORM\Column(name="coefficient", type="decimal", nullable=true, scale=2)
   */
  private $coefficient;

  /**
   * @var boolean
   * 
   * @ORM\Column(name="is_new", type="boolean", options={"default":0})
   */
  private $is_new;
  
  public function __construct(){
    if (is_object($this->entity))
      $this->entity_id = $this->entity->getId() ;
    if (is_object($this->user))
      $this->user_id = $this->user->getId() ;
  }

  public function getEntityId() {
    return is_object($this->getEntity()) ? $this->getEntity()->getId() : $this->entity_id;
  }

  public function getUserId() {
    return is_object($this->getUser()) ? $this->getUser()->getId() : $this->user_id;
  }

  public function getChecked() {
    return $this->checked;
  }

  /**
   * Set user
   *
   * @param User $user
   * @return ProductSelection
   */
  public function setUser(User $user) {
    $this->user = $user;
    $this->user_id = $user->getId();

    return $this;
  }

  /**
   * Get user
   *
   * @return User 
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Set show_price
   *
   * @param boolean $showPrice
   * @return ProductSelection
   */
  public function setShowPrice($showPrice) {
    $this->show_price = $showPrice;

    return $this;
  }

  /**
   * Get show_price
   *
   * @return boolean 
   */
  public function getShowPrice() {
    return $this->show_price;
  }

  /**
   * Set show_promotion
   *
   * @param boolean $showPromotion
   * @return ProductSelection
   */
  public function setShowPromotion($showPromotion) {
    $this->show_promotion = $showPromotion;

    return $this;
  }

  /**
   * Get show_promotion
   *
   * @return boolean 
   */
  public function getShowPromotion() {
    return $this->show_promotion;
  }

  /**
   * Set show_exclusivity
   *
   * @param boolean $showExclusivity
   * @return ProductSelection
   */
  public function setShowExclusivity($showExclusivity) {
    $this->show_exclusivity = $showExclusivity;

    return $this;
  }

  /**
   * Get show_exclusivity
   *
   * @return boolean 
   */
  public function getShowExclusivity() {
    return $this->show_exclusivity;
  }

  /**
   * Set coefficient
   *
   * @param string $coefficient
   * @return ProductSelection
   */
  public function setCoefficient($coefficient) {
    $this->coefficient = $coefficient;

    return $this;
  }

  /**
   * Get coefficient
   *
   * @return string 
   */
  public function getCoefficient() {
    return $this->coefficient;
  }

  /**
   * Set is_new
   *
   * @param boolean $isNew
   * @return ProductSelection
   */
  public function setIsNew($isNew) {
    $this->is_new = $isNew;

    return $this;
  }

  /**
   * Get is_new
   *
   * @return boolean 
   */
  public function getIsNew() {
    return $this->is_new;
  }

  /**
   * Set entity
   *
   * @param \Sogedial\SiteBundle\Entity\Produit $entity
   * @return ProductSelection
   */
  public function setEntity(\Sogedial\SiteBundle\Entity\Produit $entity) {
    $this->entity = $entity;
    $this->entity_id = $entity->getId();
    return $this;
  }

  /**
   * Get entity
   *
   * @return \Sogedial\SiteBundle\Entity\Produit 
   */
  public function getEntity() {
    return $this->entity;
  }

}
