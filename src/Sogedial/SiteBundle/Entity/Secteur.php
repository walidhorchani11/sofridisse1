<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Secteur
 *
 * @ORM\Table(name="secteur")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\SecteurRepository")
 */
class Secteur
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_secteur", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Rayon",  mappedBy="secteur", fetch="EXTRA_LAZY")
     */
    private $rayons;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit",  mappedBy="secteur", fetch="EXTRA_LAZY")
     */
    private $produits;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->rayons = new ArrayCollection();
        $this->produits = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param string $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    /**
     * @return Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param Datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Add rayons
     *
     * @param Rayon $rayons
     * @return $this
     */
    public function addRayon(Rayon $rayons)
    {
        $this->rayons[] = $rayons;

        return $this;
    }

    /**
     * Remove rayons
     *
     * @param Rayon $rayons
     */
    public function removeRayon(Rayon $rayons)
    {
        $this->rayons->removeElement($rayons);
    }

    /**
     * Get rayons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRayons()
    {
        return $this->rayons;
    }

    /**
     * Add produits
     *
     * @param Produit $produits
     * @return $this
     */
    public function addProduit(Produit $produits)
    {
        $this->produits[] = $produits;

        return $this;
    }

    /**
     * Remove produits
     *
     * @param Produit $produits
     */
    public function removeProduit(Produit $produits)
    {
        $this->produits->removeElement($produits);
    }

    /**
     * Get produits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProduits()
    {
        return $this->produits;
    }

}