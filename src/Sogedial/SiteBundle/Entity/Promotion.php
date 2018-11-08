<?php

namespace Sogedial\SiteBundle\Entity;
use Sogedial\SiteBundle\Entity\MoreStockRequest;

use Doctrine\ORM\Mapping as ORM;

/**
 * Promotion
 *
 * @ORM\Table(name="promotion")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\PromotionRepository")
 */
class Promotion
{

     /**
     * @var string
     *
     * @ORM\Column(name="code_promotion", type="string", length=55, unique=true, nullable=false)
     * @ORM\Id
     */
    private $code;

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
     * @var Datetime
     *
     * @ORM\Column(name="date_debut_validite", type="datetime")
     */
    private $dateDebutValidite;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_fin_validite", type="datetime")
     */
    private $dateFinValidite;

    /**
     * @var Enseigne
     *
     * @ORM\ManyToOne(targetEntity="Enseigne", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_enseigne", referencedColumnName="code_enseigne")
     */
    private $enseigne;

    /**
     * @var string
     *
     * @ORM\Column(name="code_traitement_exception", type="string", length=1)
     */
    private $codeTraitementException;

    /**
     * @var string
     *
     * @ORM\Column(name="revente_perte", type="string", length=125)
     */
    private $reventePerte;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_ht", type="string", length=255)
     */
    private $prixHt;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
     */
    private $client;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var Supplier
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Supplier", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_supplier", referencedColumnName="code_supplier")
     */
    private $supplier;

    /**
     * @var Produit
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @var string
     *
     * @ORM\Column(name="regroupement_client", type="string", length=3, nullable=true)
     */
    private $regroupementClient;

    /**
     * @var string
     *
     * @ORM\Column(name="code_category_client", type="string", length=125, nullable=true)
     */
    private $codeCategoryClient;

    /**
     * @var string
     *
     * @ORM\Column(name="code_type_promo", type="string", length=125, nullable=true)
     */
    private $codeTypePromo;

    /**
     * @var string
     *
     * @ORM\Column(name="stock_engagement", type="string", length=15, nullable=true)
     */
    private $stockEngagement;

    /**
     * @var string
     *
     * @ORM\Column(name="stock_engagement_restant", type="string", length=15, nullable=true)
     */
    private $stockEngagementRestant;

    /**
     * @var string
     *
     * @ORM\Column(name="stock_engagement_demande", type="string", length=15, nullable=true)
     */
    private $stockEngagementDemande;

    /**
     * @var string
     *
     * @ORM\Column(name="demande_en_cours", type="string", length=15, nullable=true)
     */
    private $commandeEnCours;

    /**
     * @var string
     *
     * @ORM\Column(name="commande_facture", type="string", length=15, nullable=true)
     */
    private $commandeFacture;

    /**
     * @var MoreStockRequest
     * 
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\MoreStockRequest", mappedBy="promotion")
     */
    private $moreStockRequestPromotion;
     
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
     * @return Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return Datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getCodeTraitementException()
    {
        return $this->codeTraitementException;
    }

    /**
     * @return string
     */
    public function getReventePerte()
    {
        return $this->reventePerte;
    }

    /**
     * @return string
     */
    public function getPrixHt()
    {
        return $this->prixHt;
    }

    /**
     * @param Datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param string $codeTraitementException
     */
    public function setCodeTraitementException($codeTraitementException)
    {
        $this->codeTraitementException = $codeTraitementException;
    }

    /**
     * @param string $reventePerte
     */
    public function setReventePerte($reventePerte)
    {
        $this->reventePerte = $reventePerte;
    }

    /**
     * @param string $prixHt
     */
    public function setPrixHt($prixHt)
    {
        $this->prixHt = $prixHt;
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
     * @return Promotion
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client
     *
     * @param Client $client
     * @return Promotion
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get Entreprise
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
     * @return Promotion
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set supplier
     *
     * @param Supplier $supplier
     * @return Promotion
     */
    public function setSupplier(Supplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
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
     * @return Datetime
     */
    public function getDateFinValidite()
    {
        return $this->dateFinValidite;
    }

    /**
     * @param Datetime $dateFinValidite
     */
    public function setDateFinValidite($dateFinValidite)
    {
        $this->dateFinValidite = $dateFinValidite;
    }

    /**
     * @return Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }

    /**
     * @param Produit|null $produit
     * @return $this
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
     * @return string
     */
    public function getRegroupementClient()
    {
        return $this->regroupementClient;
    }

    /**
     * @param string $regroupementClient
     */
    public function setRegroupementClient($regroupementClient)
    {
        $this->regroupementClient = $regroupementClient;
    }

    /**
     * @return string
     */
    public function getCodeCategoryClient()
    {
        return $this->codeCategoryClient;
    }

    /**
     * @param string $codeCategoryClient
     */
    public function setCodeCategoryClient($codeCategoryClient)
    {
        $this->codeCategoryClient = $codeCategoryClient;
    }

    /**
     * @return string
     */
    public function getCodeTypePromo()
    {
        return $this->codeTypePromo;
    }

    /**
     * @param string $codeTypePromo
     */
    public function setCodeTypePromo($codeTypePromo)
    {
        $this->codeTypePromo = $codeTypePromo;
    }

    /**
    * @return string
    */
    public function getStockEngagement()
    {
        return $this->stockEngagement;
    }

    /**
     * @param string $stockRestant
     * @return void
     */
    public function setStockEngagement($stockRestant)
    {
        $this->stockEngagement = $stockRestant;
    }

    /**
    * @return string
    */
    public function getStockEngagementRestant()
    {
        return $this->stockEngagementRestant;
    }

    /**
     * @param $stockRestantEngagement
     * @return void
     */
    public function setStockEngagementRestant($stockRestantEngagement)
    {
        $this->stockEngagementRestant = $stockRestantEngagement;
    }

    /**
    * @return string
    */
    public function getStockEngagementDemande()
    {
        return $this->stockEngagementDemande;
    }

    /**
     * @param string $stockRestantDemande
     * @return void
     */
    public function setStockEngagementDemande($stockEngagementDemande)
    {
        $this->stockEngagementDemande = $stockEngagementDemande;
    }

   /**
   * @return string
   */
    public function getCommandeEnCours()
    {
        return $this->commandeEnCours;
    }

   /**
   * @param string $commandeEnCours
   * @return void
   */
    public function setCommandeEnCours($commandeEnCours)
    {
        $this->commandeEnCours = $commandeEnCours;
    }

    /**
    * @return string
    */
    public function getCommandeFacture()
    {
        return $this->commandeFacture;
    }

    /**
    * @param string $commandeFacture
    * @return void
    */
    public function setCommandeFacture($commandeFacture)
    {
        $this->commandeFacture = $commandeFacture;
    }
}
