<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Events;
use FOS\ElasticaBundle\Configuration\Search;

/**
 * Produit
 *
 * @ORM\Table(name="produit",indexes={@ORM\Index(name="ean13_idx", columns={"ean13_produit"})})
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ProduitRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\TranslationEntity(class="Sogedial\SiteBundle\Entity\Translation\ProductTranslation")
 */
class Produit implements Translatable
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_produit", type="string", length=55, unique=true, nullable=false)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="denomination_produit_base", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $denominationProduitBase;

    /**
     * @var string
     *
     * @ORM\Column(name="denomination_produit_long", type="text")
     * @Gedmo\Translatable
     */
    private $denominationProduitLong;

    /**
     * @var string
     *
     * @ORM\Column(name="denomination_produit_court", type="string", length=125)
     * @Gedmo\Translatable
     */
    private $denominationProduitCourt;

    /**
     * @var string
     *
     * @ORM\Column(name="denomination_produit_caisse", type="string", length=125)
     * @Gedmo\Translatable
     */
    private $denominationProduitCaisse;

    /**
     * @var string
     *
     * @ORM\Column(name="poids_variable", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $poidsVariable;

    /**
     * @var string
     *
     * @ORM\Column(name="description_produit", type="text")
     * @Gedmo\Translatable
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="format_produit", type="string", length=255)
     */
    private $format;

    /**
     * @var string
     *
     * @ORM\Column(name="dlc_produit", type="string", length=255)
     */
    private $dlc;

    /**
     * @var string
     *
     * @ORM\Column(name="dlc_moyenne", type="string", length=255)
     */
    private $dlcMoyenne;

    /**
     * @var string
     *
     * @ORM\Column(name="dlc_garantie_produit", type="string", length=255)
     */
    private $dlcGarantie;

    /**
     * @var string
     *
     * @ORM\Column(name="ean13_produit", type="string", length=13)
     */
    private $ean13;

    /**
     * @var string
     *
     * @ORM\Column(name="ingredients_produits", type="text")
     * @Gedmo\Translatable
     */
    private $ingredients;

    /**
     * @var string
     *
     * @ORM\Column(name="rhf_produit", type="string", length=255, nullable=true)
     */
    private $rhf;

    /**
     * @var string
     *
     * @ORM\Column(name="origine", type="string", length=255)
     */
    private $origine;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature", type="string", length=255)
     */
    private $temperature;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Colis", mappedBy="produit")
     */
    private $colis;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Nutrition", mappedBy="produit")
     */
    private $nutrition;

    /**
     * @Gedmo\Locale
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_validation", nullable=true)
     */
    private $translationValidation;

    /**
     * @var string
     *
     * @ORM\Column(name="ndp", type="string", length=255)
     */
    private $ndp;

    /**
     * @var boolean
     *
     * @ORM\Column(name="actif", type="boolean")
     */
    private $actif = true;

    /**
     * @var string
     *
     * @ORM\Column(name="marketing_code", type="text")
     */
    private $marketingCode;

    /**
     * @var string
     *
     * @ORM\Column(name="nature_code", type="text")
     */
    private $natureCode;

    /**
     * @var string
     *
     * @ORM\Column(name="special_suivi", type="text")
     */
    private $specialSuivi;

    /**
     * @var string
     *
     * @ORM\Column(name="tva_code", type="text")
     */
    private $tvaCode;

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
     * @var string
     *
     * @ORM\Column(name="contenance", type="string", length=60)
     */
    private $contenance;

    /**
     * @var string
     *
     * @ORM\Column(name="pcb", type="string", length=60)
     */
    private $pcb;

    /**
     * @var string
     *
     * @ORM\Column(name="sale_unity", type="text")
     */
    private $saleUnity;

    /**
     * @var string
     *
     * @ORM\Column(name="sale_command_mesure_unity", type="text")
     */
    private $saleCommandMesureUnity;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pre_commande", type="boolean")
     */
    private $preCommande;

    /**
     * @var ProduitRegle
     *
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\ProduitRegle", mappedBy="code", fetch="EXTRA_LAZY")
     */
    private $quantiteMinimale;

    /**
     * @var string
     *
     * @ORM\Column(name="alcool", type="string", nullable=true)
     */
    private $alcool;

    /**
     * @var string
     *
     * @ORM\Column(name="liquide", type="string", nullable=true)
     */
    private $liquide;

    /**
     * @var datetime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var datetime
     *
     * @ORM\Column(name="ended_at", type="datetime", nullable=true)
     */
    private $endedAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Promotion", mappedBy="produit", fetch="EXTRA_LAZY")
     */
    private $promotions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Tarif", mappedBy="produit", fetch="EXTRA_LAZY")
     */
    private $tarifs;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Degressif", mappedBy="produit", fetch="EXTRA_LAZY")
     */
    private $degressifs;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\LigneCommande", mappedBy="produit", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    private $lignes;

    /**
     * @var Departement
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Departement", inversedBy="produits")
     * @ORM\JoinColumn(name="code_departement", referencedColumnName="code_departement")
     */
    private $departement;

    /**
     * @var Secteur
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Secteur", inversedBy="produits")
     * @ORM\JoinColumn(name="code_secteur", referencedColumnName="code_secteur")
     */
    private $secteur;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var ArrayCollection Produit $suppliers
     *
     * Inverse side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\Supplier", mappedBy="produits", fetch="EXTRA_LAZY")
     */
    private $suppliers;

    /**
     * @var ArrayCollection RechercheMot $recherche_mots
     *
     * Inverse side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\RechercheMot", mappedBy="produits", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"})
     */
    private $recherche_mots;

    /**
     * @var Rayon
     *
     * @ORM\ManyToOne(targetEntity="Rayon", inversedBy="produits")
     * @ORM\JoinColumn(name="code_rayon", referencedColumnName="code_rayon")
     */
    private $rayon;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Assortiment", mappedBy="produit", fetch="EXTRA_LAZY")
     */
    private $assortiments;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\ProductCoef",  mappedBy="product", cascade={"persist"})
     */
    private $productCoef;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\UserBundle\Entity\ProductSelection",  mappedBy="entity", cascade={"persist"})
     */
    private $selections;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Marque", inversedBy="produits")
     * @ORM\JoinColumn(nullable=true, name="code_marque", referencedColumnName="code_marque")
     */
    private $marque;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Photo", mappedBy="produit", cascade={"persist"})
     */
    private $photos;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Famille", inversedBy="produits")
     * @ORM\JoinColumn(nullable=true, name="code_famille", referencedColumnName="code_famille")
     */
    private $famille;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\SousFamille", inversedBy="produits")
     * @ORM\JoinColumn(nullable=true, name="code_sous_famille", referencedColumnName="code_sous_famille")
     */
    private $sousFamille;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Segment", inversedBy="produits")
     * @ORM\JoinColumn(nullable=true, name="code_segment", referencedColumnName="code_segment")
     */
    private $segment;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Stock", mappedBy="produit")
     */
    private $stock;

/**
     * @var string
     *
     * @ORM\Column(name="libelle_marque", type="string", length=255, nullable=true)
     */
    private $libelleMarque;

    /**
     * @var string
     *
     * @ORM\Column(name="code_entrepot", nullable=true, type="string", length=60)
     */
    private $codeEntrepot;

    /**
     * @var string
     *
     * @ORM\Column(name="le_mans", nullable=true, type="string", length=60)
     */
    private$leMans;

    /**
     * @var string
     *
     * @ORM\Column(name="q", nullable=true, type="string", length=60)
     */
    private $q;

    /**
     * @var string
     *
     * @ORM\Column(name="pan", nullable=true, type="string", length=60)
     */
    private $pan;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_de_cession", nullable=true, type="string", length=60)
     */
    private $prixDeCession;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_preste", nullable=true, type="string", length=60)
     */
    private $prixPreste;

    /**
     * @var string
     *
     * @ORM\Column(name="duree_vie_jours", nullable=true, type="string", length=11)
     */
    private $dureeVieJours;

    /**
     * @var string
     *
     * @ORM\Column(name="contrat_date_logidis", nullable=true, type="string", length=255)
     */
    private $contratDateLogidis;

    /**
     * @var datetime
     *
     * @ORM\Column(name="fin_validite_article", type="datetime", nullable=true)
     */
    private $finValiditeArticle;

    /**
     * @var string
     *
     * @ORM\Column(name="code_ifls_de_remplacement", nullable=true, type="string", length=60)
     */
    private $codeIflsDeRemplacement;

    /**
     * @var string
     *
     * @ORM\Column(name="arfbi", nullable=true, type="string", length=60)
     */
    private $arfbi;
    
    /**
     * @var string
     *
     * @ORM\Column(name="cfe", nullable=true, type="string", length=60)
     */
    private $cfe;

    /**
     * @var string
     *
     * @ORM\Column(name="rgt", nullable=true, type="string", length=60)
     */
    private $rgt;

    /**
     * @var string
     *
     * @ORM\Column(name="code_disponibilite", nullable=true, type="string", length=60)
     */
    private $codeDisponibilite;

    /**
     * @var string
     *
     * @ORM\Column(name="code_fournisseur", nullable=true, type="string", length=60)
     */
    private $codeFournisseur;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_fournisseur", nullable=true, type="string", length=125)
     */
    private $nomFournisseur;

    /**
     * @var string
     *
     * @ORM\Column(name="departement_fournisseur", nullable=true, type="string", length=10)
     */
    private $departementFournisseur;

    /**
     * @var string
     *
     * @ORM\Column(name="code_dangereux", nullable=true, type="string", length=5)
     */
    private $codeDangereux;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_new", type="boolean", nullable=true)
     */
    private $isNew;

    /**
     * @var string
     *
     * @ORM\Column(name="pieces_art_k", nullable=true, type="string", length=3)
     */
    private $piecesArtK;

    /**
     * @var boolean
     *
     * @ORM\Column(name="in_achat_logidis", type="boolean", nullable=true)
     */
    private $inAchatLogidis;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_blacklisted", type="boolean", nullable=true)
     */
    private $blacklisted;

    /**
     * @var datetime
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
     private $deletedAt;
    
    public function __construct()
    {
        $this->photos = array();
        $this->degressifs = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->suppliers = new ArrayCollection();
        $this->assortiments = new ArrayCollection();
        $this->lignes = new ArrayCollection();
        $this->recherche_mots = new ArrayCollection();
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
    public function getDenominationProduitLong()
    {
        return $this->denominationProduitLong;
    }

    /**
     * @param string $denominationProduitLong
     */
    public function setDenominationProduitLong($denominationProduitLong)
    {
        $this->denominationProduitLong = $denominationProduitLong;
    }

    /**
     * @return string
     */
    public function getDenominationProduitCourt()
    {
        return $this->denominationProduitCourt;
    }

    /**
     * @param string $denominationProduitCourt
     */
    public function setDenominationProduitCourt($denominationProduitCourt)
    {
        $this->denominationProduitCourt = $denominationProduitCourt;
    }

    /**
     * @return string
     */
    public function getDenominationProduitCaisse()
    {
        return $this->denominationProduitCaisse;
    }

    /**
     * @param string $denominationProduitCaisse
     */
    public function setDenominationProduitCaisse($denominationProduitCaisse)
    {
        $this->denominationProduitCaisse = $denominationProduitCaisse;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getDlc()
    {
        return $this->dlc;
    }

    /**
     * @param string $dlc
     */
    public function setDlc($dlc)
    {
        $this->dlc = $dlc;
    }

    /**
     * @return string
     */
    public function getDlcMoyenne()
    {
        return $this->dlcMoyenne;
    }

    /**
     * @param string $dlcMoyenne
     */
    public function setDlcMoyenne($dlcMoyenne)
    {
        $this->dlcMoyenne = $dlcMoyenne;
    }

    /**
     * @return string
     */
    public function getDlcGarantie()
    {
        return $this->dlcGarantie;
    }

    /**
     * @param string $dlcGarantie
     */
    public function setDlcGarantie($dlcGarantie)
    {
        $this->dlcGarantie = $dlcGarantie;
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
    public function getIngredients()
    {
        return $this->ingredients;
    }

    /**
     * @param string $ingredients
     */
    public function setIngredients($ingredients)
    {
        $this->ingredients = $ingredients;
    }

    /**
     * @return string
     */
    public function getRhf()
    {
        return $this->rhf;
    }

    /**
     * @param string $rhf
     */
    public function setRhf($rhf)
    {
        $this->rhf = $rhf;
    }

    /**
     * @return string
     */
    public function getOrigine()
    {
        return $this->origine;
    }

    /**
     * @param string $origine
     */
    public function setOrigine($origine)
    {
        $this->origine = $origine;
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
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getNdp()
    {
        return $this->ndp;
    }

    /**
     * @param string $ndp
     */
    public function setNdp($ndp)
    {
        $this->ndp = $ndp;
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
     * @return string
     */
    public function getSpecialSuivi()
    {
        return $this->specialSuivi;
    }

    /**
     * @param string $specialSuivi
     */
    public function setSpecialSuivi($specialSuivi)
    {
        $this->specialSuivi = $specialSuivi;
    }

    /**
     * @return string
     */
    public function getTvaCode()
    {
        return $this->tvaCode;
    }

    /**
     * @param string $tvaCode
     */
    public function setTvaCode($tvaCode)
    {
        $this->tvaCode = $tvaCode;
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
    public function getContenance()
    {
        return $this->contenance;
    }

    /**
     * @param string $contenance
     */
    public function setContenance($contenance)
    {
        $this->contenance = $contenance;
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
    public function getSaleCommandMesureUnity()
    {
        return $this->saleCommandMesureUnity;
    }

    /**
     * @param string $saleCommandMesureUnity
     */
    public function setSaleCommandMesureUnity($saleCommandMesureUnity)
    {
        $this->saleCommandMesureUnity = $saleCommandMesureUnity;
    }

    /**
     * @return string
     */
    public function getAlcool()
    {
        return $this->alcool;
    }

    /**
     * @param string $alcool
     */
    public function setAlcool($alcool)
    {
        $this->alcool = $alcool;
    }

    /**
     * @return string
     */
    public function getLiquide()
    {
        return $this->liquide;
    }

    /**
     * @param string $liquide
     */
    public function setLiquide($liquide)
    {
        $this->liquide = $liquide;
    }

    /**
     * Add photos
     *
     * @param Photo $photos
     * @return Photo
     */
    public function addPhoto(Photo $photos)
    {
        $this->photos[] = $photos;
        $photos->setProduit($this);

        return $this;
    }

    /**
     * Remove photos
     *
     * @param Photo $photos
     */
    public function removePhoto(Photo $photos)
    {
        $this->photos->removeElement($photos);
    }

    /**
     * Get photos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    public function hasPhoto(Photo $photo)
    {
        foreach ($this->photos as $p) {
            if ($p->getSource() == $photo->getSource()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set TranslatableLocale
     *
     * @param string $locale
     * @return Produit
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * get ImageCover
     *
     * @return Image or false if no cover image is set
     */
    public function getImageCover()
    {
        $photoCover = (count($this->photos) > 0) ? $this->photos[0] : null;
        foreach ($this->photos as $photo) {
            if ($photo->getCover()) {
                $photoCover = $photo;
            }
        }

        if (!$photoCover || !file_exists(__DIR__ . '/../../../../web/images/product/original/' . $photoCover->getSource()))
            return null;

        return $photoCover;
    }

    /**
     * has ImageCover
     *
     * @return true if a cover image is set for the product
     */
    public function hasImageCover()
    {
        $hasCover = false;
        foreach ($this->photos as $photo) {
            if ($photo->getCover()) {
                $hasCover = true;
            }
        }
        return $hasCover;
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
     * Set segment
     *
     * @param Segment|null $segment
     * @return $this
     */
    public function setSegment(Segment $segment = null)
    {
        $this->segment = $segment;

        return $this;
    }

    /**
     * Get segment
     *
     * @return Segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    public function addTranslationValidation($translationValidation)
    {

        if (!in_array($translationValidation, $this->translationValidation, true)) {
            $this->translationValidation[] = $translationValidation;
        }

        return $this;
    }

    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getTranslationValidation()
    {
        return $this->translationValidation;
    }

    /**
     * Set translationValidation
     *
     * @param array $translationValidation
     * @return Produit
     */
    public function setTranslationValidation($translationValidation)
    {
        $this->translationValidation = $translationValidation;

        return $this;
    }

    public function removeTranslationValidation($translationValidation)
    {

        if (in_array($translationValidation, $this->translationValidation, true)) {
            unset($this->translationValidation[$translationValidation]);
        }

        return $this;
    }

    /**
     * Set nutrition
     *
     * @param Nutrition $nutrition
     * @return Produit
     */
    public function setNutrition(Nutrition $nutrition = null)
    {
        $this->nutrition = $nutrition;

        return $this;
    }

    /**
     * Get nutrition
     *
     * @return Nutrition
     */
    public function getNutrition()
    {
        return $this->nutrition;
    }

    /**
     * Add selections
     *
     * @param \Sogedial\UserBundle\Entity\ProductSelection $selections
     * @return Produit
     */
    public function addSelection(\Sogedial\UserBundle\Entity\ProductSelection $selections)
    {
        $this->selections[] = $selections;
        $selections->setEntity($this);
        return $this;
    }

    /**
     * Remove selections
     *
     * @param \Sogedial\UserBundle\Entity\ProductSelection $selections
     */
    public function removeSelection(\Sogedial\UserBundle\Entity\ProductSelection $selections)
    {
        $this->selections->removeElement($selections);
    }

    /**
     * Get selections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSelections()
    {
        return $this->selections;
    }


    public function filterAssortiment($user_id)
    {
        $tmp = array();
        foreach ($this->selections as $selection) {
            if ($selection->getUser()->getId() == $user_id) {
                $tmp[] = $selection;
            }
        }
        return $tmp;
    }

    public function setEntreprise(Entreprise $entreprise)
    {
        if ($entreprise instanceof Entreprise ) {
            $this->entreprise = $entreprise;
        } else {
            throw new \Exception("$entreprise must be an instance of Entreprise");
        }
    }

    /**
     * Get Entreprise
     *
     * @return Entreprise $entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
    }

    /**
     * Set colis
     *
     * @param Colis $colis
     * @return Produit
     */
    public function setColis(Colis $colis = null)
    {
        $this->colis = $colis;

        return $this;
    }

    /**
     * Get colis
     *
     * @return Colis
     */
    public function getColis()
    {
        return $this->colis;
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
     * Add assortiments
     *
     * @param Assortiment $assortiments
     * @return $this
     */
    public function addAssortiment(Assortiment $assortiments)
    {
        $this->assortiments[] = $assortiments;

        return $this;
    }

    /**
     * Remove assortiments
     *
     * @param Assortiment $assortiments
     */
    public function removeAssortiment(Assortiment $assortiments)
    {
        $this->assortiments->removeElement($assortiments);
    }

    /**
     * Get assortiments
     *
     * @return ArrayCollection
     */
    public function getAssortiments()
    {
        return $this->assortiments;
    }

    /**
     * @return datetime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param datetime $startedAt
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return datetime
     */
    public function getEndedAt()
    {
        return $this->endedAt;
    }

    /**
     * @param datetime $endedAt
     */
    public function setEndedAt($endedAt)
    {
        $this->endedAt = $endedAt;
    }

    /**
     * Add promotions
     *
     * @param Promotion $promotions
     * @return $this
     */
    public function addPromotion(Promotion $promotions)
    {
        $this->promotions[] = $promotions;

        return $this;
    }

    /**
     * Remove promotions
     *
     * @param Promotion $promotions
     */
    public function removePromotion(Promotion $promotions)
    {
        $this->promotions->removeElement($promotions);
    }

    /**
     * Get promotions
     *
     * @return ArrayCollection
     */
    public function getPromotion()
    {
        return $this->promotions;
    }

    /**
     * Add degressif
     *
     * @param \Sogedial\SiteBundle\Entity\Degressif $degressif
     *
     * @return Produit
     */
    public function addDegressif(\Sogedial\SiteBundle\Entity\Degressif $degressif)
    {
        $this->degressifs[] = $degressif;

        return $this;
    }

    /**
     * Remove degressif
     *
     * @param \Sogedial\SiteBundle\Entity\Degressif $degressif
     */
    public function removeDegressif(\Sogedial\SiteBundle\Entity\Degressif $degressif)
    {
        $this->degressifs->removeElement($degressif);
    }

    /**
     * Get degressifs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDegressifs()
    {
        return $this->degressifs;
    }

    /**
     * Add tarifs
     *
     * @param Tarif $tarifs
     * @return $this
     */
    public function addTarif(Tarif $tarifs)
    {
        $this->tarifs[] = $tarifs;

        return $this;
    }

    /**
     * Remove tarifs
     *
     * @param Tarif $tarifs
     */
    public function removeTarif(Tarif $tarifs)
    {
        $this->tarifs->removeElement($tarifs);
    }

    /**
     * Get tarifs
     *
     * @return ArrayCollection
     */
    public function getTarifs()
    {
        return $this->tarifs;
    }

    /**
     * Add RechercheMot
     *
     * @param RechercheMot $recherche_mot
     */
    public function addRechercheMot(RechercheMot $recherche_mot)
    {
        // ajout d'un seul recherche_mot pour un produit donné ;
        // création du lien bi-directionnel dans les entités Produit et RechercheMot
        if (!$this->recherche_mots->contains($recherche_mot)) {
            // pas clair : comment le lien inverse peut exister déjà si le lien direct n'existe pas ?
            if (!$recherche_mot->getProduits()->contains($this)) {
                $recherche_mot->setProduits($this);
            }
            $this->recherche_mots->add($recherche_mot);
        }
    }

    public function setRechercheMots($items)
    {
        if ($items instanceof ArrayCollection || is_array($items)) {
            foreach ($items as $item) {
                $this->addRechercheMot($item);
            }
        } elseif ($items instanceof RechercheMot) {
            $this->addRechercheMot($items);
        } else {
            throw new \Exception("$items must be an instance of RechercheMot or ArrayCollection");
        }
    }

    /**
     * Get ArrayCollection
     *
     * @return ArrayCollection $recherche_mots
     */
    public function getRechercheMots()
    {
        return $this->recherche_mots;
    }

    /**
     * Add Supplier
     *
     * @param Supplier $supplier
     */
    public function addSupplier(Supplier $supplier)
    {
        if (!$this->suppliers->contains($supplier)) {
            if (!$supplier->getProduits()->contains($this)) {
                $supplier->setProduits($this);
            }
            $this->suppliers->add($supplier);
        }
    }

    public function setSuppliers($items)
    {
        if ($items instanceof ArrayCollection || is_array($items)) {
            foreach ($items as $item) {
                $this->addSupplier($item);
            }
        } elseif ($items instanceof Supplier) {
            $this->addSupplier($items);
        } else {
            throw new \Exception("$items must be an instance of Supplier or ArrayCollection");
        }
    }

    /**
     * Get ArrayCollection
     *
     * @return ArrayCollection $suppliers
     */
    public function getSuppliers()
    {
        return $this->suppliers;
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
     * Set departement
     *
     * @param Departement|null $departement
     * @return $this
     */
    public function setDepartement(Departement $departement = null)
    {
        $this->departement = $departement;

        return $this;
    }

    /**
     * Get departement
     *
     * @return Departement
     */
    public function getDepartement()
    {
        return $this->departement;
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


    public function getLignes()
    {
        return $this->lignes->toArray();
    }

    public function addLigne(LigneCommande $ligne)
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setProduit($this);
        }

        return $this;
    }

    public function removeLigne(LigneCommande $ligne)
    {
        if ($this->lignes->contains($ligne)) {
            $this->lignes->removeElement($ligne);
            $ligne->setProduit(null);
        }

        return $this;
    }

    public function getCommandes()
    {
        return array_map(
            function ($ligne) {
                return $ligne->getCommande();
            },
            $this->lignes->toArray()
        );
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
     * @return boolean
     */
    public function getPreCommande()
    {
        return $this->preCommande;
    }

    /**
     * @param boolean $preCommande
     */
    public function setPreCommande($preCommande)
    {
        $this->preCommande = $preCommande;
    }

    /**
     * @return ProduitRegle
     */
    public function getQuantiteMinimale()
    {
        return $this->quantiteMinimale;
    }

    /**
     * @param ProduitRegle $quantiteMinimale
     */
    public function setQuantiteMinimalee($quantiteMinimale)
    {
        $this->quantiteMinimale = $quantiteMinimale;
    }

     /**
     * @return string
     */
    public function getLibelleMarque()
    {
        return $this->libelleMarque;
    }

    /**
     * @param string $libelleMarque
     */
    public function setLibelleMarque($libelleMarque)
    {
        $this->libelleMarque = $libelleMarque;
    }

    /**
     * @return string
     */
    public function getCodeEntrepot()
    {
        return $this->codeEntrepot;
    }

    /**
     * @param string $codeEntrepot
     */
    public function setCodeEntrepot($codeEntrepot)
    {
        $this->codeEntrepot = $codeEntrepot;
    }
/**
     * @return string
     */
    public function getLeMans()
    {
        return $this->leMans;
    }

    /**
     * @param string $leMans
     */
    public function setLeMans($leMans)
    {
        $this->leMans = $leMans;
    }

    
    /**
     * @return string
     */
    public function getQ()
    {
        return $this->q;
    }

    /**
     * @param string $q
     */
    public function setQ($q)
    {
        $this->q = $q;
    }

    /**
     * @return string
     */
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * @param string $pan
     */
    public function setPan($pan)
    {
        $this->pan = $pan;
    }

    /**
     * @return string
     */
    public function getPrixDeCession()
    {
        return $this->prixDeCession;
    }

    /**
     * @param string $prixDeCession
     */
    public function setPrixDeCession($prixDeCession)
    {
        $this->prixDeCession = $prixDeCession;
    }

    /**
     * @return string
     */
    public function getPrixPreste()
    {
        return $this->prixPreste;
    }

    /**
     * @param string $prixPreste
     */
    public function setPrixPreste($prixPreste)
    {
        $this->prixPreste = $prixPreste;
    }

    /**
     * @return int
     */
    public function getDureeVieJours()
    {
        return $this->dureeVieJours;
    }

    /**
     * @param int $dureeVieJours
     */
    public function setDureeVieJours($dureeVieJours)
    {
        $this->dureeVieJours = $dureeVieJours;
    }

    /**
     * @return string
     */
    public function getContratDateLogidis()
    {
        return $this->contratDateLogidis;
    }

    /**
     * @param string $contratDateLogidis
     */
    public function setContratDateLogidis($contratDateLogidis)
    {
        $this->contratDateLogidis = $contratDateLogidis;
    }

    /**
     * @return datetime
     */
    public function getFinValiditeArticle()
    {
        return $this->finValiditeArticle;
    }

    /**
     * @param datetime $finValiditeArticle
     */
    public function setFinValiditeArticle($finValiditeArticle)
    {
        $this->finValiditeArticle = $finValiditeArticle;
    }

    /**
     * @return string
     */
    public function getCodeIflsDeRemplacement()
    {
        return $this->codeIflsDeRemplacement;
    }

    /**
     * @param string $codeIflsDeRemplacement
     */
    public function setCodeIflsDeRemplacement($codeIflsDeRemplacement)
    {
        $this->codeIflsDeRemplacement = $codeIflsDeRemplacement;
    }

    /**
     * @return string
     */
    public function getArfbi()
    {
        return $this->arfbi;
    }

    /**
     * @param string $arfbi
     */
    public function setArfbi($arfbi)
    {
        $this->arfbi = $arfbi;
    }
    
    /**
     * @return string
     */
    public function getCfe()
    {
        return $this->cfe;
    }

    /**
     * @param string $cfe
     */
    public function setCfe($cfe)
    {
        $this->cfe = $cfe;
    }

    /**
     * @return string
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param string $rgt
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    }

    /**
     * @return string
     */
    public function getCodeDisponibilite()
    {
        return $this->codeDisponibilite;
    }

    /**
     * @param string $codeDisponibilite
     */
    public function setCodeDisponibilite($codeDisponibilite)
    {
        $this->codeDisponibilite = $codeDisponibilite;
    }

    /**
     * @return string
     */
    public function getCodeFournisseur()
    {
        return $this->codeFournisseur;
    }

    /**
     * @param string $codeFournisseur
     */
    public function setCodeFournisseur($codeFournisseur)
    {
        $this->codeFournisseur = $codeFournisseur;
    }

    /**
     * @return string
     */
    public function getNomFournisseur()
    {
        return $this->nomFournisseur;
    }

    /**
     * @param string $nomFournisseur
     */
    public function setNomFournisseur($nomFournisseur)
    {
        $this->nomFournisseur = $nomFournisseur;
    }

    /**
     * @return string
     */
    public function getDepartementFournisseur()
    {
        return $this->departementFournisseur;
    }

    /**
     * @param string $departementFournisseur
     */
    public function setDepartementFournisseur($departementFournisseur)
    {
        $this->departementFournisseur = $departementFournisseur;
    }

    /**
     * @return string
     */
    public function getCodeDangereux()
    {
        return $this->codeDangereux;
    }

    /**
     * @param string $codeDangereux
     */
    public function setCodeDangereux($codeDangereux)
    {
        $this->codeDangereux = $codeDangereux;
    }

     /**
     * @return boolean
     */
    public function isIsNew()
    {
        return $this->isNew;
    }

    /**
     * @param boolean $isNew
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    /**
     * @return string
     */
    public function getPiecesArtK()
    {
        return $this->piecesArtK;
    }

    /**
     * @param string $piecesArtK
     */
    public function setPiecesArtK($piecesArtK)
    {
        $this->piecesArtK = $piecesArtK;
    }

    /**
     * @return boolean
     */
    public function getInAchatLogidis()
    {
        return $this->inAchatLogidis;
    }

    /**
     * @param boolean $inAchatLogidis
     */
    public function setInAchatLogidis($inAchatLogidis)
    {
        $this->inAchatLogidis = $inAchatLogidis;
    }

    /**
     * @return boolean
     */
    public function getBlacklisted()
    {
        return $this->blacklisted;
    }

    /**
     * @param boolean $blacklisted
     */
    public function setBlacklisted($blacklisted)
    {
        $this->blacklisted = $blacklisted;
    
    }

    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }
 
    /**
    * @param mixed $deletedAt
    */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;     
    }
 }
