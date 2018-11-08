<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sogedial\SiteBundle\Entity\OrderOrderStatus
 *
 * @ORM\Table(name="commande_etatcommande")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\OrderOrderStatusRepository")
 */
class OrderOrderStatus
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Commande")
     */
    private $order;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\OrderStatus", cascade={"persist"})
     */
    private $orderStatus;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return OrderOrderStatus
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set order
     *
     * @param \Sogedial\SiteBundle\Entity\Commande $order
     * @return OrderOrderStatus
     */
    public function setOrder(\Sogedial\SiteBundle\Entity\Commande $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Sogedial\SiteBundle\Entity\Commande
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set orderStatus
     *
     * @param \Sogedial\SiteBundle\Entity\OrderStatus $orderStatus
     * @return OrderOrderStatus
     */
    public function setOrderStatus(\Sogedial\SiteBundle\Entity\OrderStatus $orderStatus)
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return \Sogedial\SiteBundle\Entity\OrderStatus
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }
}
