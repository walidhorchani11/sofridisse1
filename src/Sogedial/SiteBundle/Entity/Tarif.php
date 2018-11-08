<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Session
 *
 * @ORM\Table(name="tarif")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\TarifRepository")
 */
class Tarif
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_tarif", type="string", length=55, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var Enseigne
     *
     * @ORM\ManyToOne(targetEntity="Enseigne", inversedBy="tarifs")
     * @ORM\JoinColumn(name="code_enseigne", referencedColumnName="code_enseigne")
     */
    private $enseigne;

     /**
     * @var Tarification
     *
     * @ORM\ManyToOne(targetEntity="Tarification", inversedBy="tarifs")
     * @ORM\JoinColumn(name="code_tarification", referencedColumnName="code_tarification")
     */
    private $tarification;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_ht", type="string", length=255, nullable=true)
     */
    private $prixHt;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_pvc", type="string", length=255, nullable=true)
     */
    private $prixPvc;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_debut_validite", type="datetime", nullable=true)
     */
    private $dateDebutValidite;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="tarifs")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var string
     *
     * @ORM\Column(name="code_ean_13", type="string", length=13, nullable=true,)
     */
    private $codeEan13;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="tarifs")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var Produit
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="tarifs")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
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
    public function getPrixHt()
    {
        return $this->prixHt;
    }

    /**
     * @param string $prixHt
     */
    public function setPrixHt($prixHt)
    {
        $this->prixHt = $prixHt;
    }

    /**
     * @return string
     */
    public function getPrixPvc()
    {
        return $this->prixPvc;
    }

    /**
     * @param string $prixPvc
     */
    public function setPrixPvc($prixPvc)
    {
        $this->prixPvc = $prixPvc;
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
     * @return Datetime
     */
    public function getDateDebutValidite()
    {
        return $this->dateDebutValidite;
    }

    /**
     * @param Datetime $dateDebutValidite
     */
    public function setDateDebutValidite($dateDebutValidite)
    {
        $this->dateDebutValidite = $dateDebutValidite;
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
     * Set region
     *
     * @param Region $region
     * @return Tarif
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeEan13()
    {
        return $this->codeEan13;
    }

    /**
     * @param string $codeEan13
     */
    public function setCodeEan13($codeEan13)
    {
        $this->codeEan13 = $codeEan13;
    }

    /**
     * Get entreprise
     *
     * @return Entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
    }

    /**
     * Set entreprise
     *
     * @param Entreprise $entreprise
     * @return Tarif
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * Get produit
     *
     * @return Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }

    /**
     * Set produit
     *
     * @param Produit $produit
     * @return Tarif
     */
    public function setProduit(Produit $produit = null)
    {
        $this->produit = $produit;

        return $this;
    }

    /**
     * Set enseigne
     *
     * @param Enseigne|null $enseigne
     * @return $this
     */
    public function setEnseigne(Enseigne $enseigne = null)
    {
        $this->enseigne = $enseigne;

        return $this;
    }

    /**
     * get enseigne
     *
     * @return Enseigne
     */
    public function getEnseigne()
    {
        return $this->enseigne;
    }

    /**
     * Set tarification
     *
     * @param Tarification|null $tarification
     * @return $this
     */
    public function setTarification(Tarification $tarification = null)
    {
        $this->tarification = $tarification;

        return $this;
    }

    /**
     * get tarification
     *
     * @return Tarification
     */
    public function getTarification()
    {
        return $this->tarification;
    }

}