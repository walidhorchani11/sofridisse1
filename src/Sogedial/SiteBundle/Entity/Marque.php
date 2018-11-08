<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Marque
 *
 * @ORM\Table(name="marque")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\MarqueRepository")
 */
class Marque
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_marque", type="string", length=64, nullable=true, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     *
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="url_logo", type="string", length=255, nullable=true)
     */
    private $urlLogo;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="marque", fetch="EXTRA_LAZY")
     */
    private $produits;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_mdd", type="boolean", nullable=true)
     */
    private $isMdd;

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
     * Set urlLogo
     *
     * @param string $urlLogo
     * @return Marque
     */
    public function setUrlLogo($urlLogo)
    {
        $this->urlLogo = $urlLogo;

        return $this;
    }

    /**
     * Get urlLogo
     *
     * @return string
     */
    public function getUrlLogo()
    {
        return $this->urlLogo;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Marque
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->produits = new ArrayCollection();
    }

    /**
     * Add produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     * @return Marque
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
     * @return boolean
     */
    public function isIsMdd()
    {
        return $this->isMdd;
    }

    /**
     * @param boolean $isMdd
     */
    public function setIsMdd($isMdd)
    {
        $this->isMdd = $isMdd;
    }
}
