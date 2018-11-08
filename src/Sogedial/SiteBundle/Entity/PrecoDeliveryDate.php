<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * PrecoDeliveryDate
 *
 * @ORM\Table(name="preco_delivery_date")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\PrecoDeliveryDateRepository")
 */
class PrecoDeliveryDate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var $deliveryFrequency
     *
     ** @ORM\Column( name="delivery_frequency", type="integer", nullable=true )
     */
    private $deliveryFrequency;

    /**
     * @var $orderFrequency
     *
     ** @ORM\Column( name="order_frequency", type="integer", nullable=true )
     */
    private $orderFrequency;

    /**
     * @var $identifiantSociete
     *
     * @ORM\Column( name="identifiant_societe", type="integer", nullable=true )
     */
    private $identifiantSociete;

    /**
     * @var $libelleSociete
     *
     * @ORM\Column(name="libelle_societe", type="string", length=60, nullable=true)
     */
    private $libelleSociete;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDeliveryFrequency()
    {
        return $this->deliveryFrequency;
    }

    /**
     * @return mixed
     */
    public function getOrderFrequency()
    {
        return $this->orderFrequency;
    }

    /**
     * @return mixed
     */
    public function getIdentifiantSociete()
    {
        return $this->identifiantSociete;
    }

    /**
     * @return mixed
     */
    public function getLibelleSociete()
    {
        return $this->libelleSociete;
    }

    /**
     * @param mixed $deliveryFrequency
     */
    public function setDeliveryFrequency($deliveryFrequency)
    {
        $this->deliveryFrequency = $deliveryFrequency;
    }

    /**
     * @param mixed $orderFrequency
     */
    public function setOrderFrequency($orderFrequency)
    {
        $this->orderFrequency = $orderFrequency;
    }

    /**
     * @param mixed $identifiantSociete
     */
    public function setIdentifiantSociete($identifiantSociete)
    {
        $this->identifiantSociete = $identifiantSociete;
    }

    /**
     * @param mixed $libelleSociete
     */
    public function setLibelleSociete($libelleSociete)
    {
        $this->libelleSociete = $libelleSociete;
    }


}