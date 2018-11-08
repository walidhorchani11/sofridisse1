<?php

namespace Sogedial\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Events;
use Sogedial\SiteBundle\Entity\MetaClient;
use \Datetime;

/**
 * User
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="Sogedial\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class User extends BaseUser
{

    /**
     * @ORM\Id
     * @ORM\Column(name="id_utilisateur", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="utilisateurs", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom_utilisateur", type="string", length=255, nullable=true)
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_utilisateur", type="string", length=255, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="pays_vente", type="string", length=255, nullable=true)
     */
    private $paysVente;

    /**
     * @var integer
     *
     * @ORM\Column(name="ca_utilisateur", type="string", length=255, nullable=true)
     */
    private $chiffreAffaire;

    /**
     * @var string
     *
     * @ORM\Column(name="nature_utilisateur", type="string", length=255, nullable=true)
     */
    private $nature;

    /**
     * @var string
     *
     * @ORM\Column(name="poste_utilisateur", type="string", length=255, nullable=true)
     */
    private $poste;

    /**
     * @var string
     *
     * @ORM\Column(name="numero1_utilisateur", type="string", length=255, nullable=true)
     */
    private $numero1;

    /**
     * @var string
     *
     * @ORM\Column(name="numero2_utilisateur", type="string", length=255, nullable=true)
     */
    private $numero2;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_utilisateur", type="string", length=255, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="etat", type="string", length=255, nullable=true)
     */
    private $etat;

    /**
     * @var string
     *
     * @ORM\Column(name="statut_utilisateur", type="string", length=255, nullable=true)
     */
    private $statut;

    /**
     * @var string
     *
     * @ORM\Column(name="importation_france", type="string", length=255, nullable=true)
     */
    private $importationFrance;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Session", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $session;

    /**
     * @ORM\OneToMany(targetEntity="ProductSelection",  mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $product_selections;

    /**
     * @ORM\OneToMany(targetEntity="FamilySelection",  mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $family_selections;

    /**
     * @var string
     *
     * @ORM\Column(name="locale_utilisateur", type="string", length=255, nullable=true)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_utilisateur", type="string", length=255, nullable=true))
     */
    private $commentaire;

    /**
     * @var string
     *
     * @ORM\Column(name="gamme_utilisateur", type="array", nullable=true))
     */
    private $gamme;

    /**
     * @var string
     *
     * @ORM\Column(name="produit_demande_utilisateur", type="string", length=255, nullable=true))
     */
    private $produitsDemande;

    /**
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit")
     * @ORM\JoinTable(name="user_produits",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")}
     *      )
     */
    private $produitsSelectionne;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Zone", fetch="EAGER")
     * @ORM\JoinColumn(name="code_zone_sec", referencedColumnName="code_zone")
     */
    private $zoneSec;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Zone", fetch="EAGER")
     * @ORM\JoinColumn(name="code_zone_frais", referencedColumnName="code_zone")
     */
    private $zoneFrais;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Zone", fetch="EAGER")
     * @ORM\JoinColumn(name="code_zone_surgele", referencedColumnName="code_zone")
     */
    private $zoneSurgele;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cgv_cpv", type="boolean", nullable=true)
     */
    private $cgvCpv;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="cgv_cpv_signed_at", type="datetime", nullable=true)
     */
    private $cgvCpvSignedAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="cgv_cpv_updated_at", type="datetime", nullable=true)
     */
    private $cgvCpvUpdatedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="already_signed", type="boolean", nullable=true)
     */
    private $alreadySigned;

    /**
     * @var boolean
     *
     * @ORM\Column(name="flag_franco", type="boolean", nullable=true)
     */
    private $flagFranco;

    /**
     * @var float
     *
     * @ORM\Column(name="montant_franco", type="float", nullable=true )
     */
    private $montantFranco;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\MetaClient", inversedBy="user")
     * @ORM\JoinColumn(nullable=true, name="meta", referencedColumnName="code_meta")
     */
    private $meta;

    /**
     * @var string
     *
     * @ORM\Column(name="entreprise_courante", type="string", length=255, nullable=true))
     */
    private $entrepriseCourante;

    /**
     * @var smallint
     *
     * @ORM\Column(name="pre_commande", type="smallint", nullable=true))
     */
    private $preCommande;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_debut_validite", type="datetime", nullable=true)
     */
    private $dateDebutValidite;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_fin_validite", type="datetime", nullable=true)
     */
    private $dateFinValidite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="premiere_visite", type="boolean", nullable=true, options={"default": 0 })
     */
    private $premiereVisite;

    public function __construct()
    {
        parent::__construct();
        $this->tracking = new ArrayCollection();
        $this->product_selections = new ArrayCollection();
        $this->family_selections = new ArrayCollection();
        $this->gamme = array();
        $date = new Datetime();
        $date->setTime(0, 0, 0);
        $this->dateDebutValidite = $date;
    }

    /**
     * Adds a role to the user.
     * @param string $role
     * @return User
     */
    public function addRole($role)
    {
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * get roles
     * @return Roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function clearId()
    {
        $this->id = null; // également essayé avec "", 0, valeur de l'auto-incrément, true, false, -1

        return $this;
    }

    /**
     * Set entreprise
     *
     * @param \Sogedial\SiteBundle\Entity\Entreprise $entreprise
     * @return User
     */
    public function setEntreprise(\Sogedial\SiteBundle\Entity\Entreprise $entreprise)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * Get entreprise
     *
     * @return \Sogedial\SiteBundle\Entity\Entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     * @return User
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set paysVente
     *
     * @param string $paysVente
     * @return User
     */
    public function setPaysVente($paysVente)
    {
        $this->paysVente = $paysVente;

        return $this;
    }

    /**
     * Get paysVente
     *
     * @return string
     */
    public function getPaysVente()
    {
        return $this->paysVente;
    }

    /**
     * Set chiffreAffaire
     *
     * @param integer $chiffreAffaire
     * @return User
     */
    public function setChiffreAffaire($chiffreAffaire)
    {
        $this->chiffreAffaire = $chiffreAffaire;

        return $this;
    }

    /**
     * Get chiffreAffaire
     *
     * @return integer
     */
    public function getChiffreAffaire()
    {
        return $this->chiffreAffaire;
    }

    /**
     * Set nature
     *
     * @param string $nature
     * @return User
     */
    public function setNature($nature)
    {
        $this->nature = $nature;

        return $this;
    }

    /**
     * Get nature
     *
     * @return string
     */
    public function getNature()
    {
        return $this->nature;
    }

    /**
     * Set poste
     *
     * @param string $poste
     * @return User
     */
    public function setPoste($poste)
    {
        $this->poste = $poste;

        return $this;
    }

    /**
     * Get poste
     *
     * @return string
     */
    public function getPoste()
    {
        return $this->poste;
    }

    /**
     * Set numero1
     *
     * @param string $numero1
     * @return User
     */
    public function setNumero1($numero1)
    {
        $this->numero1 = $numero1;

        return $this;
    }

    /**
     * Get numero1
     *
     * @return string
     */
    public function getNumero1()
    {
        return $this->numero1;
    }

    /**
     * Set numero2
     *
     * @param string $numero2
     * @return User
     */
    public function setNumero2($numero2)
    {
        $this->numero2 = $numero2;

        return $this;
    }

    /**
     * Get numero2
     *
     * @return string
     */
    public function getNumero2()
    {
        return $this->numero2;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return User
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set etat
     *
     * @param string $etat
     * @return User
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat
     *
     * @return string
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set statut
     *
     * @param string $statut
     * @return User
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Get statut
     *
     * @return string
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Set importationFrance
     *
     * @param string $importationFrance
     * @return User
     */
    public function setImportationFrance($importationFrance)
    {
        $this->importationFrance = $importationFrance;

        return $this;
    }

    /**
     * Get importationFrance
     *
     * @return string
     */
    public function getImportationFrance()
    {
        return $this->importationFrance;
    }

    public function getAvailableCountry()
    {
        return array(
            'France' => 'France',
            'Angleterre' => 'Angleterre'
        );
    }

    public function getAvailableSales()
    {
        return array(
            'De 0M€ à 1M€' => 'De 0M€ à 1M€',
            'De 1M€ à 5M€' => 'De 1M€ à 5M€',
            'De 5M€ à 10M€' => 'De 5M€ à 10M€',
            'De 10M€ à 50M€' => 'De 10M€ à 50M€'
        );
    }

    public function getAvailableNature()
    {
        return array(
            'Importateur' => 'Importateur',
            'Distributeur' => 'Distributeur'
        );
    }

    public function getAvailableState()
    {
        return array(
            'Client' => 'Client',
            'Prospect' => 'Prospect'
        );
    }

    public function getAvailableRole()
    {
        return array(
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_USER' => 'Client'
        );
    }

    public function getAvailableStatus()
    {
        return array(
            'lock',
            'deny',
            'pending',
            'validate'
        );
    }

    /**
     * Add session
     *
     * @param \Sogedial\SiteBundle\Entity\Session $session
     * @return User
     */
    public function addSession(\Sogedial\SiteBundle\Entity\Session $session)
    {
        $this->session[] = $session;

        return $this;
    }

    /**
     * Remove session
     *
     * @param \Sogedial\SiteBundle\Entity\Session $session
     */
    public function removeSession(\Sogedial\SiteBundle\Entity\Session $session)
    {
        $this->session->removeElement($session);
    }

    /**
     * Get session
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return User
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get zoneSec
     *
     * @return \Sogedial\SiteBundle\Entity\Zone
     */
    public function getZoneSec()
    {
        return $this->zoneSec;
    }

    /**
     * @param \Sogedial\SiteBundle\Entity\Zone
     */
    public function setZoneSec(\Sogedial\SiteBundle\Entity\Zone $zoneSec)
    {
        $this->zoneSec = $zoneSec;
    }

    /**
     * Get zoneFrais
     *
     * @return \Sogedial\SiteBundle\Entity\Zone
     */
    public function getZoneFrais()
    {
        return $this->zoneFrais;
    }

    /**
     * @param \Sogedial\SiteBundle\Entity\Zone
     */
    public function setZoneFrais(\Sogedial\SiteBundle\Entity\Zone $zoneFrais)
    {
        $this->zoneFrais = $zoneFrais;
    }

    /**
     * Get zoneSurgele
     *
     * @return \Sogedial\SiteBundle\Entity\Zone
     */
    public function getZoneSurgele()
    {
        return $this->zoneSurgele;
    }

    /**
     * @param \Sogedial\SiteBundle\Entity\Zone
     */
    public function setZoneSurgele(\Sogedial\SiteBundle\Entity\Zone $zoneSurgele)
    {
        $this->zoneSurgele = $zoneSurgele;
    }


    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
        $this->created = new \Datetime();
        $this->updated = new \Datetime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \Datetime();
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return User
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        if (empty($this->locale)) {
            return 'fr';
        }

        return $this->locale;
    }

    /**
     * Set commentaire
     *
     * @param string $commentaire
     * @return User
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set gamme
     *
     * @param string $gamme
     * @return User
     */
    public function setGamme($gamme)
    {
        $this->gamme = $gamme;

        return $this;
    }

    /**
     * Get gamme
     *
     * @return string
     */
    public function getGamme()
    {
        return $this->gamme;
    }

    /**
     * Set produitsDemande
     *
     * @param string $produitsDemande
     * @return User
     */
    public function setProduitsDemande($produitsDemande)
    {
        $this->produitsDemande = $produitsDemande;

        return $this;
    }

    /**
     * Get produitsDemande
     *
     * @return string
     */
    public function getProduitsDemande()
    {
        return $this->produitsDemande;
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
     * Add product_selections
     *
     * @param \Sogedial\UserBundle\Entity\ProductSelection $productSelection
     * @return User
     */
    public function addProductSelection(\Sogedial\UserBundle\Entity\ProductSelection $productSelection)
    {
        foreach ($this->product_selections as $key => $select) {
            if ($select->getEntityId() == $productSelection->getEntityId() && $select->getUserId() == $productSelection->getUserId()
            ) {
                $select->setShowPrice($productSelection->getShowPrice());
                $select->setShowPromotion($productSelection->getShowPromotion());
                $select->setShowExclusivity($productSelection->getShowExclusivity());
                $select->setCoefficient($productSelection->getCoefficient());
                $select->setIsNew($productSelection->getIsNew());

                return $this;
            }
        }
        $this->product_selections[] = $productSelection;

        return $this;
    }

    /**
     * Remove product_selections
     *
     * @param \Sogedial\UserBundle\Entity\ProductSelection $productSelection
     */
    public function removeProductSelection(\Sogedial\UserBundle\Entity\ProductSelection $productSelection)
    {
        foreach ($this->product_selections as $key => $select) {
            if ($select->getEntityId() == $productSelection->getEntityId() && $select->getUserId() == $productSelection->getUserId()
            ) {
                $this->product_selections->remove($key);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Set product_selections
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $productSelections
     * @return type
     */
    public function setProductSelections(ArrayCollection $productSelections)
    {
        $this->product_selections = $productSelections;

        return $this;
    }

    /**
     * Get product_selections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductSelections()
    {
        return $this->product_selections;
    }

    /**
     * Add family_selections
     *
     * @param \Sogedial\UserBundle\Entity\FamilySelection $familySelection
     * @return User
     */
    public function addFamilySelection(\Sogedial\UserBundle\Entity\FamilySelection $familySelection)
    {
        foreach ($this->family_selections as &$select) {
            if ($select->getEntityId() == $familySelection->getEntityId() && $select->getUserId() == $familySelection->getUserId()
            ) {
                $select->setShowPrice($familySelection->getShowPrice());
                $select->setShowPromotion($familySelection->getShowPromotion());
                $select->setShowExclusivity($familySelection->getShowExclusivity());
                $select->setCoefficient($familySelection->getCoefficient());
                $select->setIsNew($familySelection->getIsNew());

                return $this;
            }
        }
        $this->family_selections[] = $familySelection;

        return $this;
    }

    /**
     * Remove family_selections
     *
     * @param \Sogedial\UserBundle\Entity\FamilySelection $familySelection
     */
    public function removeFamilySelection(\Sogedial\UserBundle\Entity\FamilySelection $familySelection)
    {
        foreach ($this->family_selections as $key => $select) {
            if ($select->getEntityId() == $familySelection->getEntityId() && $select->getUserId() == $familySelection->getUserId()
            ) {
                $this->family_selections->remove($key);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Get family_selections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFamilySelections()
    {
        return $this->family_selections;
    }

    /**
     * Set family_selections
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $familySelections
     * @return type
     */
    public function setFamilySelections(ArrayCollection $familySelections)
    {
        $this->family_selections = $familySelections;

        return $this;
    }

    public function getTotalTime()
    {
        $total = 0;
        foreach ($this->session as $session) {
            $routes = $session->getRoutes();
            if (count($routes) > 1) {
                $total += strtotime($routes[count($routes) - 1]->getDate()->format('Y-m-d H:i:s')) - strtotime(
                        $routes[0]->getDate()->format('Y-m-d H:i:s')
                    );
            }
        }

        return $total;
    }

    public function getCountConnexion()
    {
        return count($this->session);
    }

    /**
     * Add produitsSelectionne
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produitsSelectionne
     * @return User
     */
    public function addProduitsSelectionne(\Sogedial\SiteBundle\Entity\Produit $produitsSelectionne)
    {
        $this->produitsSelectionne[] = $produitsSelectionne;

        return $this;
    }

    /**
     * Remove produitsSelectionne
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produitsSelectionne
     */
    public function removeProduitsSelectionne(\Sogedial\SiteBundle\Entity\Produit $produitsSelectionne)
    {
        $this->produitsSelectionne->removeElement($produitsSelectionne);
    }

    /**
     * Get produitsSelectionne
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProduitsSelectionne()
    {
        return $this->produitsSelectionne;
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
    public function getCgvCpv()
    {
        return $this->cgvCpv;
    }

    /**
     * @param string $cgvCpv
     */
    public function setCgvCpv($cgvCpv)
    {
        $this->cgvCpv = $cgvCpv;
    }

    /**
     * @return Datetime
     */
    public function getCgvCpvSignedAt()
    {
        return $this->cgvCpvSignedAt;
    }

    /**
     * @param Datetime $cgvCpvSignedAt
     */
    public function setCgvCpvSignedAt($cgvCpvSignedAt)
    {
        $this->cgvCpvSignedAt = $cgvCpvSignedAt;
    }

    /**
     * @return Datetime
     */
    public function getCgvCpvUpdatedAt()
    {
        return $this->cgvCpvUpdatedAt;
    }

    /**
     * @param Datetime $cgvCpvUpdatedAt
     */
    public function setCgvCpvUpdatedAt($cgvCpvUpdatedAt)
    {
        $this->cgvCpvUpdatedAt = $cgvCpvUpdatedAt;
    }

    /**
     * @return boolean
     */
    public function isAlreadySigned()
    {
        return $this->alreadySigned;
    }

    /**
     * @param boolean $alreadySigned
     */
    public function setAlreadySigned($alreadySigned)
    {
        $this->alreadySigned = $alreadySigned;
    }

    /**
     * @return bool
     */
    public function isFlagFranco()
    {
        return $this->flagFranco;
    }

    /**
     * @param bool $flagFranco
     */
    public function setFlagFranco($flagFranco)
    {
        $this->flagFranco = $flagFranco;
    }

    /**
     * @return float
     */
    public function getMontantFranco()
    {
        return $this->montantFranco;
    }

    /**
     * @param float $montantFranco
     */
    public function setMontantFranco($montantFranco)
    {
        $this->montantFranco = $montantFranco;
    }

    /**
     * @return string
     */
    public function getEntrepriseCourante()
    {
        return $this->entrepriseCourante;
    }

    /**
     * @param string $entrepriseCourante
     */
    public function setEntrepriseCourante($entrepriseCourante)
    {
        $this->entrepriseCourante = $entrepriseCourante;
    }

    /**
     * @param MetaClient $meta
     * @return $this
     */
    public function setMeta(\Sogedial\SiteBundle\Entity\MetaClient $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }

    public function getUser(){
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPreCommande()
    {
        return $this->preCommande;
    }

    /**
    * @param int $preCommande
    */
    public function setPrecommande($preCommande){
        return $this->preCommande = $preCommande;
    }

    public function __toString()
    {
        return $this->entreprise->getRaisonSociale();
    }

    /**
     * @return bool
     */
    public function isPremiereVisite()
    {
        return $this->premiereVisite;
    }

    /**
     * @param bool $premiereVisite
     */
    public function setPremiereVisite($premiereVisite)
    {
        $this->premiereVisite = $premiereVisite;
    } 
}