<?php

namespace Sogedial\SiteBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Rayon
 *
 * @ORM\Table(name="rayon")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\RayonRepository")
 */
class Rayon
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_rayon", type="string", length=11, nullable=false, unique=true)
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
     * @var string
     *
     * @ORM\Column(name="valeur", type="string", length=11, nullable=true)
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="rayon", fetch="EXTRA_LAZY")
     */
    private $produits;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Famille", mappedBy="rayon", fetch="EXTRA_LAZY")
     */
    private $familles;

    /**
     * @var Secteur
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Secteur", inversedBy="rayons")
     * @ORM\JoinColumn(name="code_secteur", referencedColumnName="code_secteur")
     */
    private $secteur;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;


    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->produits = new ArrayCollection();
        $this->familles = new ArrayCollection();
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
     * Set region
     *
     * @param Region $region
     * @return Rayon
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
     * Add produits
     *
     * @param Produit $produits
     * @return Rayon
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
     * @return ArrayCollection
     */
    public function getProduits()
    {
        return $this->produits;
    }

    /**
     * Add familles
     *
     * @param Famille $familles
     * @return Rayon
     */
    public function addFamille(Famille $familles)
    {
        $this->familles[] = $familles;

        return $this;
    }

    /**
     * Remove familles
     *
     * @param Famille $familles
     */
    public function removeFamille(Famille $familles)
    {
        $this->produits->removeElement($familles);
    }

    /**
     * Get familles
     *
     * @return ArrayCollection
     */
    public function getFamilles()
    {
        return $this->familles;
    }

    /**
     * Set secteur
     *
     * @param Secteur|null $secteur
     * @return $this
     */
    public function setSecteur(Secteur $secteur = null)
    {
        $this->secteur = $secteur;

        return $this;
    }

    /**
     * Get secteur
     *
     * @return Secteur
     */
    public function getSecteur()
    {
        return $this->secteur;
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

}