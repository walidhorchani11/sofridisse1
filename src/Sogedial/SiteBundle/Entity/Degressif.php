<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Degressif
 *
 * @ORM\Table(name="degressif")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\DegressifRepository")
 */
class Degressif
{
	/**
     * @var string
     *
     * longueur du champ : code société = 3 + tiret + code produit = 13 + tiret + palier = 6
     *
     * @ORM\Column(name="code_degressif", type="string", length=24, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;
	/**
     * @var Produit
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="degressifs")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;
	
	/**
     * @var integer
     *
     * @ORM\Column(name="palier", type="integer", nullable=false)
     */
	private $palier;
	
	/**
     * @var float
     *
     * @ORM\Column(name="prix_ht", type="float", nullable=false)
     */
	private $prixHt;
	/**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Degressif
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set palier
     *
     * @param integer $palier
     *
     * @return Degressif
     */
    public function setPalier($palier)
    {
        $this->palier = $palier;

        return $this;
    }

    /**
     * Get palier
     *
     * @return integer
     */
    public function getPalier()
    {
        return $this->palier;
    }

    /**
     * Set prixHt
     *
     * @param float $prixHt
     *
     * @return Degressif
     */
    public function setPrixHt($prixHt)
    {
        $this->prixHt = $prixHt;

        return $this;
    }

    /**
     * Get prixHt
     *
     * @return float
     */
    public function getPrixHt()
    {
        return $this->prixHt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Degressif
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Degressif
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
     *
     * @return Degressif
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit = null)
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
}
