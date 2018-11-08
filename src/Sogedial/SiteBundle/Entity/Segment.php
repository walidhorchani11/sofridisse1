<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Segment
 *
 * @ORM\Table(name="segment")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\SegmentRepository")
 */
class Segment
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_segment", type="string", length=11, nullable=false, unique=true)
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
     * @ORM\Column(name="valeur", type="string", length=255)
     */
    private $valeur;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Famille
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\SousFamille", inversedBy="segments")
     * @ORM\JoinColumn(name="code_sous_famille", referencedColumnName="code_sous_famille")
     */
    private $sousFamille;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="segment")
     */
    private $produits;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->produits = new ArrayCollection();
    }

    /**
     * Set sousFamille
     *
     * @param SousFamille|null $sousFamille
     * @return $this
     */
    public function setSousFamille(SousFamille $sousFamille = null)
    {
        $this->sousFamille = $sousFamille;

        return $this;
    }

    /**
     * Get sousFamille
     *
     * @return SousFamille
     */
    public function getSousFamille()
    {
        return $this->sousFamille;
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
     * Add produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     * @return Segment
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