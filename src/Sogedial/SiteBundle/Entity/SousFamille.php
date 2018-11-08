<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * SousFamille
 *
 * @ORM\Table(name="sous_famille")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\SousFamilleRepository")
 */
class SousFamille
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_sous_famille", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;
	
	/**
     * @var string
     *
     * @ORM\Column(name="libelle_sommaire", type="string", length=255, nullable=true)
     */
    private $libelleSommaire;

    /**
     * @var string
     *
     * @ORM\Column(name="valeur", type="string", length=255)
     */
    private $valeur;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Famille
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Famille", inversedBy="sousFamilles")
     * @ORM\JoinColumn(name="code_famille", referencedColumnName="code_famille")
     */
    private $famille;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Segment",  mappedBy="sousFamille", fetch="EXTRA_LAZY")
     */
    private $segments;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="sousFamille")
     */
    private $produits;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->segments = new ArrayCollection();
        $this->produits = new ArrayCollection();
    }

    /**
     * Set famille
     *
     * @param Famille|null $famille
     * @return $this
     */
    public function setFamille(Famille $famille = null)
    {
        $this->famille = $famille;

        return $this;
    }

    /**
     * Get famille
     *
     * @return Famille
     */
    public function getFamille()
    {
        return $this->famille;
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
     * Set libelleSommaire
     *
     * @param string $libelleSommaire
     *
     * @return SousFamille
     */
    public function setLibelleSommaire($libelleSommaire)
    {
        $this->libelleSommaire = $libelleSommaire;

        return $this;
    }

    /**
     * Get libelleSommaire
     *
     * @return string
     */
    public function getLibelleSommaire()
    {
        return $this->libelleSommaire;
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return SousFamille
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getValeur()
    {
        return $this->valeur;
    }

    /**
     * @param string $valeur
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;
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
     * Add segments
     *
     * @param Segment $segments
     * @return $this
     */
    public function addSegment(Segment $segments)
    {
        $this->segments[] = $segments;

        return $this;
    }

    /**
     * Remove segments
     *
     * @param Segment $segments
     */
    public function removeSegment(Segment $segments)
    {
        $this->segments->removeElement($segments);
    }

    /**
     * Get segments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Add produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     * @return SousFamille
     */
    public function addProduit(\Sogedial\SiteBundle\Entity\Produit $produits)
    {
        $this->produits[] = $produits;

        return $this;
    }

    /**
     * Remove produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     */
    public function removeProduit(\Sogedial\SiteBundle\Entity\Produit $produits)
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