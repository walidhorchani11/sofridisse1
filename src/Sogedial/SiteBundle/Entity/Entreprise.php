<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Entreprise
 *
 * @ORM\Table(name="entreprise")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\EntrepriseRepository")
 */
class Entreprise
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_entreprise", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="valeur", type="string", length=11, nullable=true)
     */
    private $valeur;

    /**
     * @var string
     *
     * @ORM\Column(name="raison_sociale", type="string", length=255, nullable=true)
     */
    private $raisonSociale;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse1", type="string", length=255, nullable=true)
     */
    private $adresse1;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse2", type="string", length=255, nullable=true)
     */
    private $adresse2;

    /**
     * @var string
     *
     * @ORM\Column(name="code_postal", type="string", length=255, nullable=true)
     */
    private $codePostal;

    /**
     * @var string
     *
     * @ORM\Column(name="ville", type="string", length=255, nullable=true)
     */
    private $ville;

    /**
     * @var string
     *
     * @ORM\Column(name="pays", type="string", length=255, nullable=true)
     */
    private $pays;

    /**
     * @var boolean
     *
     * @ORM\Column(name="actif", type="boolean", nullable=true)
     */
    private $actif;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_debut_activite", type="datetime", nullable=true)
     */
    private $dateDebutActivite;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_fin_activite", type="datetime", nullable=true)
     */
    private $dateFinActivite;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\UserBundle\Entity\User", mappedBy="entreprise", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="fk_pays", referencedColumnName="id_utilisateur")
     */
    private $utilisateurs;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="entreprises")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Tarif", mappedBy="entreprise", fetch="EXTRA_LAZY")
     */
    private $tarifs;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Promotion", mappedBy="entreprise", fetch="EXTRA_LAZY")
     */
    private $promotions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Assortiment", mappedBy="entreprise", fetch="EXTRA_LAZY")
     */
    private $assortiments;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Stock", mappedBy="entreprise", fetch="EXTRA_LAZY")
     */
    private $stocks;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Client", mappedBy="entreprise", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $clients;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Supplier", mappedBy="entreprise", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $suppliers;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=10, nullable=true)
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=10, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="etablissement", type="string", length=11, nullable=false)
     */
    private $etablissement;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\JoursFeries", mappedBy="entreprise")
     */
    private $holidays;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_environnement", type="string", length=255, nullable=true)
     */
    private $nomEnvironnement;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="entreprise_parent", referencedColumnName="code_entreprise",  nullable=true)
     */
    private $entrepriseParent;

    /**
    * @var integer 
    *
    * 1 = AVION
    * 2 = BATEAU
    *
    * @ORM\Column(name="type_precommande", type="integer", nullable=true)
    */
    private $typePreCommande;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->assortiments = new ArrayCollection();
        $this->createdAt = new \DateTime('now');
        $this->clients = new ArrayCollection();
        $this->suppliers = new ArrayCollection();
        $this->holidays = new ArrayCollection();
        $this->pays = 'france';
        $this->actif = 1;
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
     * @return string
     */ 
    public function getRaisonSociale()
    {
        return $this->raisonSociale;
    }

    /**
     * @param string $raisonSociale
     */
    public function setRaisonSociale($raisonSociale)
    {
        $this->raisonSociale = $raisonSociale;
    }

    /**
     * @return string
     */
    public function getAdresse1()
    {
        return $this->adresse1;
    }

    /**
     * @param string $adresse1
     */
    public function setAdresse1($adresse1)
    {
        $this->adresse1 = $adresse1;
    }

    /**
     * @return string
     */
    public function getAdresse2()
    {
        return $this->adresse2;
    }

    /**
     * @param string $adresse2
     */
    public function setAdresse2($adresse2)
    {
        $this->adresse2 = $adresse2;
    }

    /**
     * @return string
     */
    public function getCodePostal()
    {
        return $this->codePostal;
    }

    /**
     * @param string $codePostal
     */
    public function setCodePostal($codePostal)
    {
        $this->codePostal = $codePostal;
    }

    /**
     * @return string
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * @param string $ville
     */
    public function setVille($ville)
    {
        $this->ville = $ville;
    }

    /**
     * @return string
     */
    public function getPays()
    {
        return $this->pays;
    }

    /**
     * @param string $pays
     */
    public function setPays($pays)
    {
        $this->pays = $pays;
    }

    /**
     * @return boolean
     */
    public function isActif()
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
     * @return Datetime
     */
    public function getDateDebutActivite()
    {
        return $this->dateDebutActivite;
    }

    /**
     * @param Datetime $dateDebutActivite
     */
    public function setDateDebutActivite($dateDebutActivite)
    {
        $this->dateDebutActivite = $dateDebutActivite;
    }

    /**
     * @return Datetime
     */
    public function getDateFinActivite()
    {
        return $this->dateFinActivite;
    }

    /**
     * @param Datetime $dateFinActivite
     */
    public function setDateFinActivite($dateFinActivite)
    {
        $this->dateFinActivite = $dateFinActivite;
    }

    /**
     * Add utilisateurs
     *
     * @param \Sogedial\UserBundle\Entity\User $utilisateurs
     * @return Entreprise
     */
    public function addUtilisateur(\Sogedial\UserBundle\Entity\User $utilisateurs)
    {
        $this->utilisateurs[] = $utilisateurs;

        return $this;
    }

    /**
     * Remove utilisateurs
     *
     * @param \Sogedial\UserBundle\Entity\User $utilisateurs
     */
    public function removeUtilisateur(\Sogedial\UserBundle\Entity\User $utilisateurs)
    {
        $this->utilisateurs->removeElement($utilisateurs);
    }

    /**
     * Get utilisateurs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUtilisateurs()
    {
        return $this->utilisateurs;
    }

    public function __toString()
    {
        return $this->getRaisonSociale();
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
     * @return Entreprise
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
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
    public function getPromotions()
    {
        return $this->promotions;
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
     * Add stocks
     *
     * @param Stock $stocks
     * @return $this
     */
    public function addStock(Stock $stocks)
    {
        $this->stocks[] = $stocks;

        return $this;
    }

    /**
     * Remove stocks
     *
     * @param Stock $stocks
     */
    public function removeStock(Stock $stocks)
    {
        $this->stocks->removeElement($stocks);
    }

    /**
     * Get stocks
     *
     * @return ArrayCollection
     */
    public function getStocks()
    {
        return $this->stocks;
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
     * Add clients
     *
     * @param Client $clients
     * @return $this
     */
    public function addClient(Client $clients)
    {
        $this->clients[] = $clients;

        return $this;
    }

    /**
     * Remove clients
     *
     * @param Client $clients
     */
    public function removeClient(Client $clients)
    {
        $this->clients->removeElement($clients);
    }

    /**
     * Get clients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Add suppliers
     *
     * @param Supplier $suppliers
     * @return $this
     */
    public function addSupplier(Supplier $suppliers)
    {
        $this->suppliers[] = $suppliers;

        return $this;
    }

    /**
     * Remove suppliers
     *
     * @param Supplier $suppliers
     */
    public function removeSupplier(Supplier $suppliers)
    {
        $this->suppliers->removeElement($suppliers);
    }

    /**
     * Get suppliers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }

    /**
     * @return string
     */
    public function getEtablissement()
    {
        return $this->etablissement;
    }

    /**
     * @param string $etablissement
     */
    public function setEtablissement($etablissement)
    {
        $this->etablissement = $etablissement;
    }

    /**
     * Add holidays
     *
     * @param JoursFeries $hodidays
     * @return $this
     */
    public function addHodiday(JoursFeries $holidays)
    {
        $this->holidays[] = $holidays;

        return $this;
    }

    /**
     * Remove holidays
     *
     * @param JoursFeries $hodidays
     */
    public function removeHoliday(JoursFeries $holidays)
    {
        $this->stocks->removeElement($holidays);
    }

    /**
     * Get holidays
     *
     * @return ArrayCollection
     */
    public function getHolidays()
    {
        return $this->holidays;
    }

    /**
     * @return string
     */
    public function getNomEnvironnement()
    {
        return strtolower($this->nomEnvironnement);
    }

    /**
     * @param string $nomEnvironnement
     */
    public function setNomEnvironnement($nomEnvironnement)
    {
        $this->nomEnvironnement = $nomEnvironnement;
    }

    /**
     * @return Entreprise
     */
    public function getEntrepriseParent()
    {
        return $this->entrepriseParent;
    }

    /**
     * @param Entreprise $entrepriseParent
     */
    public function setEntrepriseParent($entrepriseParent)
    {
        $this->entrepriseParent = $entrepriseParent;
    }

    /**
     * @return integer
     */
    public function getTypePreCommande()
    {
        return $this->typePreCommande;
    }

    /**
     * @param integer $typePreCommande
     */
    public function setTypePreCommande($typePreCommande)
    {
        $this->typePreCommande = $typePreCommande;
    }


    
}
