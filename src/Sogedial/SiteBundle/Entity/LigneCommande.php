<?php

namespace Sogedial\SiteBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Sogedial\SiteBundle\Entity\Promotion;

use Doctrine\ORM\Mapping as ORM;

/**
 * LigneCommande
 *
 * @ORM\Table(name="ligneCommande")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\LigneCommandeRepository")
 */
class LigneCommande
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
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="lignes")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit", nullable=FALSE)
     */
    private $produit;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Commande", inversedBy="lignes", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="commande_id", referencedColumnName="id", nullable=FALSE)
     */
    private $commande;

    /**
     * @var int
     *
     * @ORM\Column(name="quantite", type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="moq", type="boolean", nullable=true)
     */
    private $moq;


    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var float
     *
     * @ORM\Column(name="prix_unitaire", type="float")
     */
    private $prixUnitaire;

    /**
     * @var float
     *
     * @ORM\Column(name="montant_total", type="float")
     */
    private $montantTotal;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature_produit", type="string", length=64, nullable=true)
     */
    private $temperatureProduit;

    /**
     * @var string
     *
     * @ORM\Column(name="denomination_produit_base", type="string", length=255, nullable=true)
     */
    private $denominationProduitBase;

    /**
     * @var string
     *
     * @ORM\Column(name="poids_variable", type="string", length=255, nullable=true)
     */
    private $poidsVariable;

    /**
     * @var string
     *
     * @ORM\Column(name="sale_unity", type="text", nullable=true)
     */
    private $saleUnity;

    /**
     * @var string
     *
     * @ORM\Column(name="pcb", type="string", length=60, nullable=true)
     */
    private $pcb;

    /**
     * @var string
     *
     * @ORM\Column(name="ean13_produit", type="string", length=13, nullable=true)
     */
    private $ean13;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature", type="string", length=255, nullable=true)
     */
    private $temperature;

    /**
     * @var string
     *
     * @ORM\Column(name="marketing_code", type="text", nullable=true)
     */
    private $marketingCode;

    /**
     * @var string
     *
     * @ORM\Column(name="nature_code", type="text", nullable=true)
     */
    private $natureCode;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Marque")
     * @ORM\JoinColumn(nullable=true, name="code_marque", referencedColumnName="code_marque")
     */
    private $marque;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Famille")
     * @ORM\JoinColumn(nullable=true, name="code_famille", referencedColumnName="code_famille")
     */
    private $famille;

    /**
     * @var Rayon
     *
     * @ORM\ManyToOne(targetEntity="Rayon")
     * @ORM\JoinColumn(name="code_rayon", referencedColumnName="code_rayon")
     */
    private $rayon;

    /**
     * @var boolean
     *
     * @ORM\Column(name="actif", type="boolean"), options={"default":true}))
     */
    private $actif = true;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Promotion")
     * @ORM\JoinColumn(nullable=true, name="code_promotion", referencedColumnName="code_promotion")
     */
    private $promotion;
   
    /**
     * @var string
     *
     * @ORM\Column(name="volume_unitaire", type="string", length=255, nullable=true)
     */
    private $volumeUnitaire;
   
    /**
     * @var string
     *
     * @ORM\Column(name="poids_unitaire", type="string", length=255, nullable=true)
     */
    private $poidsUnitaire;
   
    /**
     * @var string
     *
     * @ORM\Column(name="volume_total", type="string", length=255, nullable=true)
     */
    private $volumeTotal;
   
    /**
     * @var string
     *
     * @ORM\Column(name="poids_total", type="string", length=255, nullable=true)
     */
    private $poidsTotal;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->quantite = 0;
        $this->prixUnitaire = 1;
        $this->montantTotal = 1;
        $this->actif = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProduit()
    {
        return $this->produit;
    }

    public function setProduit(Produit $produit = null)
    {
        $this->produit = $produit;
        return $this;
    }

    public function getCommande()
    {
        return $this->commande;
    }

    public function setCommande(Commande $commande = null)
    {
        $this->commande = $commande;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * @param int $quantite
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;

        return $this;
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
     * @return float
     */
    public function getPrixUnitaire()
    {
        return $this->prixUnitaire;
    }

    /**
     * @param float $prixUnitaire
     */
    public function setPrixUnitaire($prixUnitaire)
    {
        $this->prixUnitaire = $prixUnitaire;
    }

    /**
     * @return float
     */
    public function getMontantTotal()
    {
        return $this->montantTotal;
    }

    /**
     * @param float $montantTotal
     */
    public function setMontantTotal($montantTotal)
    {
        $this->montantTotal = $montantTotal;
    }

    /**
     * @return string
     */
    public function getTemperatureProduit()
    {
        return $this->temperatureProduit;
    }

    /**
     * @param string $temperatureProduit
     */
    public function setTemperatureProduit($temperatureProduit)
    {
        $this->temperatureProduit = $temperatureProduit;
    }

    /**
     * @return string
     */
    public function getDenominationProduitBase()
    {
        return $this->denominationProduitBase;
    }

    /**
     * @param string $denominationProduitBase
     */
    public function setDenominationProduitBase($denominationProduitBase)
    {
        $this->denominationProduitBase = $denominationProduitBase;
    }

    /**
     * @return string
     */
    public function getPoidsVariable()
    {
        return $this->poidsVariable;
    }

    /**
     * @param string $poidsVariable
     */
    public function setPoidsVariable($poidsVariable)
    {
        $this->poidsVariable = $poidsVariable;
    }

    /**
     * @return string
     */
    public function getSaleUnity()
    {
        return $this->saleUnity;
    }

    /**
     * @param string $saleUnity
     */
    public function setSaleUnity($saleUnity)
    {
        $this->saleUnity = $saleUnity;
    }

    /**
     * @return string
     */
    public function getPcb()
    {
        return $this->pcb;
    }

    /**
     * @param string $pcb
     */
    public function setPcb($pcb)
    {
        $this->pcb = $pcb;
    }

    /**
     * @return string
     */
    public function getEan13()
    {
        return $this->ean13;
    }

    /**
     * @param string $ean13
     */
    public function setEan13($ean13)
    {
        $this->ean13 = $ean13;
    }

    /**
     * @return string
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * @param string $temperature
     */
    public function setTemperature($temperature)
    {
        $this->temperature = $temperature;
    }

    /**
     * @return string
     */
    public function getMarketingCode()
    {
        return $this->marketingCode;
    }

    /**
     * @param string $marketingCode
     */
    public function setMarketingCode($marketingCode)
    {
        $this->marketingCode = $marketingCode;
    }

    /**
     * @return string
     */
    public function getNatureCode()
    {
        return $this->natureCode;
    }

    /**
     * @param string $natureCode
     */
    public function setNatureCode($natureCode)
    {
        $this->natureCode = $natureCode;
    }

    /**
     * Set marque
     *
     * @param Marque $marque
     * @return Marque
     */
    public function setMarque(Marque $marque = null)
    {
        $this->marque = $marque;

        return $this;
    }

    /**
     * Get marque
     *
     * @return Marque
     */
    public function getMarque()
    {
        return $this->marque;
    }
    
    /**
     * Set famille
     *
     * @param Famille $famille
     * @return Produit
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
     * Get rayon
     *
     * @return Rayon
     */
    public function getRayon()
    {
        return $this->rayon;
    }

    /**
     * Set rayon
     *
     * @param Rayon|null $rayon
     * @return $this
     */
    public function setRayon(Rayon $rayon = null)
    {
        $this->rayon = $rayon;

        return $this;
    }

    /**
     * @param Stock|null $stock
     * @return $this
     */
    public function setStock(Stock $stock = null)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock
     *
     * @return Stock
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * @return boolean
     */
    public function getActif()
    {
        return $this->actif;
    }

    /**
     * @param boolean $actif
     */
    public function setActif($actif)
    {
        $this->actif = $actif;
    }

    /**
     * @return boolean
     */
    public function getMOQ()
    {
        return $this->moq;
    }

    /**
     * @param boolean $moq
     */
    public function setMOQ($moq)
    {
        $this->moq = $moq;

        return $this;
    }

    /**
     * @return Promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param Promotion $moq
     */
    public function setPromotion(Promotion $promotion = null)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * @return string
     */
    public function getVolumeUnitaire()
    {
        return $this->volumeUnitaire;
    }

    /**
     * @param string $volumeUnitaire
     */
    public function setVolumeUnitaire($volumeUnitaire)
    {
        $this->volumeUnitaire = $volumeUnitaire;
    }

    /**
     * @return string
     */
    public function getPoidsUnitaire()
    {
        return $this->poidsUnitaire;
    }

    /**
     * @param string $poidsUnitaire
     */
    public function setPoidsUnitaire($poidsUnitaire)
    {
        $this->poidsUnitaire = $poidsUnitaire;
    }

    /**
     * @return string
     */
    public function getVolumeTotal()
    {
        return $this->volumeTotal;
    }

    /**
     * @param string $volumeTotal
     */
    public function setVolumeTotal($volumeTotal)
    {
        $this->volumeTotal = $volumeTotal;
    }

    /**
     * @return string
     */
    public function getPoidsTotal()
    {
        return $this->poidsTotal;
    }

    /**
     * @param string $poidsTotal
     */
    public function setPoidsTotal($poidsTotal)
    {
        $this->poidsTotal = $poidsTotal;
    }

}