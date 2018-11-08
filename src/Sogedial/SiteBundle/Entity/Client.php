<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use \Datetime;

/**
 * Client
 *
 * @ORM\Table(name="client")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ClientRepository")
 */
class Client
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_client", type="string", length=22, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255, nullable=true)
     */
    private $nom;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_debut_validite", type="datetime")
     */
    private $dateDebutValidite;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_fin_validite", type="datetime", nullable=true)
     */
    private $dateFinValidite;

    /**
     * @var string
     *
     * @ORM\Column(name="responsable1", type="string", length=255, nullable=true)
     */
    private $responsable1;

    /**
     * @var string
     *
     * @ORM\Column(name="responsable2", type="string", length=255, nullable=true)
     */
    private $responsable2;

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
     * @ORM\Column(name="code_postale", type="string", length=5, nullable=true)
     */
    private $codePostale;

    /**
     * @var string
     *
     * @ORM\Column(name="ville", type="string", length=255, nullable=true)
     */
    private $ville;

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
     * @ORM\Column(name="email", type="string", length=125, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="statut", type="string", nullable=true)
     */
    private $statut;

    /**
     * @var Enseigne
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Enseigne", inversedBy="clients")
     * @ORM\JoinColumn(name="code_enseigne", referencedColumnName="code_enseigne")
     */
    private $enseigne;

    /**
     * @var Tarification
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Tarification", inversedBy="clients")
     * @ORM\JoinColumn(name="code_tarification", referencedColumnName="code_tarification")
     */
    private $tarification;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Promotion", mappedBy="client", fetch="EXTRA_LAZY")
     */
    private $promotions;

    /**
     * @var Assortiment
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Assortiment")
     * @ORM\JoinColumn(name="code_assortiment", referencedColumnName="code_assortiment")
     */
    private $assortiment;
    
    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="clients")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="clients", fetch="EAGER")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var string
     *
     * @ORM\Column(name="regroupement_client", type="string", length=10, nullable=true)
     */
    private $regroupementClient;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\MetaClient", inversedBy="clients")
     * @ORM\JoinColumn(nullable=true, name="code_meta_client", referencedColumnName="code_meta")
     */
    private $meta;

    /**
     * @var boolean
     *
     * @ORM\Column(name="e_actif", type="boolean", nullable=true, options={"default":0})
     */
    private $e_actif;

    /**
     * @var integer
     *
     * @ORM\Column(name="promotions_compteur", type="integer", nullable=true)
     */
    private $compteurPromotions;

    /**
     * @var string
     *
     * @ORM\Column(name="pays", type="string", length=255, nullable=true, options={"default":"France"})
     */
    private $pays = "France";

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=true)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=true)
     */
    private $longitude;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_prospect", type="boolean", nullable=false, options={"default":0})
     */
    private $is_prospect;

    /**
     * @var MoreStockRequest
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\MoreStockRequest", mappedBy="client")
     */
     private $moreStockRequestClients;

    /**
     * @var string
     *
     * @ORM\Column(name="typologie_client", type="string", length=255, nullable=true)
     */
    private $typologieClient;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_prospect", type="string", length=255, nullable=true)
     */
    private $commentaireProspect;

    /**
     * @var ArrayCollection MessageClient $messageClients
     *
     * Owning side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\MessageClient", cascade={"persist"}, inversedBy="clients", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="client_message",
     *   joinColumns={@ORM\JoinColumn(name="code_client", referencedColumnName="code_client")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="id", referencedColumnName="id")}
     * )
     */
    private $messageClients;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_client_meti", type="boolean", nullable=true)
     */
    private $isClientMeti;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recipient", type="boolean", nullable=true, options={"default" = false})
     */
    private $isRecipient;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        $this->is_prospect = false;
        $this->promotions = new ArrayCollection();
        $this->dateDebutValidite = new Datetime();
        $this->messageClients = new ArrayCollection();
        $this->isRecipient = false;
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
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @return Datetime
     */
    public function getDateDebutValidite()
    {
        return $this->dateDebutValidite;
    }

    /**
     * @return Datetime
     */
    public function getDateFinValidite()
    {
        return $this->dateFinValidite;
    }

    /**
     * @return string
     */
    public function getResponsable1()
    {
        return $this->responsable1;
    }

    /**
     * @return string
     */
    public function getResponsable2()
    {
        return $this->responsable2;
    }

    /**
     * @return string
     */
    public function getCodePostale()
    {
        return $this->codePostale;
    }

    /**
     * @return string
     */
    public function getVille()
    {
        return $this->ville;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    /**
     * @param Datetime $dateDebutValidite
     */
    public function setDateDebutValidite($dateDebutValidite)
    {
        $this->dateDebutValidite = $dateDebutValidite;
    }

    /**
     * @param Datetime $dateFinValidite
     */
    public function setDateFinValidite($dateFinValidite)
    {
        $this->dateFinValidite = $dateFinValidite;
    }

    /**
     * @param string $responsable1
     */
    public function setResponsable1($responsable1)
    {
        $this->responsable1 = $responsable1;
    }

    /**
     * @param string $responsable2
     */
    public function setResponsable2($responsable2)
    {
        $this->responsable2 = $responsable2;
    }

    /**
     * @param string $codePostale
     */
    public function setCodePostale($codePostale)
    {
        $this->codePostale = $codePostale;
    }

    /**
     * @param string $ville
     */
    public function setVille($ville)
    {
        $this->ville = $ville;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
     * @return string
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * @param string $statut
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;
    }

    /**
     * @param Assortiment $assortiment
     * @return $this
     */
    public function setAssortiment(Assortiment $assortiment = null)
    {
        $this->assortiment = $assortiment;

        return $this;
    }
 
    /**
     * @return Assortiment
     */
    public function getAssortiment()
    {
        return $this->assortiment;
    } 

    /**
     * @param Region $region
     * @return $this
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
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

    /**
     * @param Entreprise $entreprise
     * @return $this
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return Entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
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

    /**
     * @return bool
     */
    public function isEActif()
    {
        return $this->e_actif;
    }

    /**
     * @param bool $e_actif
     */
    public function setEActif($e_actif)
    {
        $this->e_actif = $e_actif;
    }


    /**
     * @return integer
     */
    public function getCompteurPromotions()
    {
        return $this->compteurPromotions;
    }

    /**
     * @param integer $compteurPromotions
     */
    public function setCompteurPromotions($compteurPromotions)
    {
        $this->compteurPromotions = $compteurPromotions;
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
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return bool
     */
    public function isProspect()
    {
        return $this->is_prospect;
    }

    /**
     * @param bool $is_prospect
     */
    public function setIsProspect($is_prospect)
    {
        $this->is_prospect = $is_prospect;
    }

    /**
     * @return string
     */
    public function getTypologieClient()
    {
        return $this->typologieClient;
    }

    /**
     * @param string $typologieClient
     */
    public function setTypologieClient($typologieClient)
    {
        $this->typologieClient = $typologieClient;
    }

    /**
     * @return array
     */
    public function getListTypology()
    {
        return array(
            'Station de service' => 'Station de service',
            'Superette' => 'Superette',
            'Hyper' => 'Hyper',
            'Autre' => 'Autre'
        );
    }

    /**
     * @return string
     */
    public function getCommentaireProspect()
    {
        return $this->commentaireProspect;
    }

    /**
     * @param string $commentaireProspect
     */
    public function setCommentaireProspect($commentaireProspect)
    {
        $this->commentaireProspect = $commentaireProspect;
    }

    /**
     * Add MessageClient
     *
     * @param MessageClient $messageClient
     */
    public function addMessageClient(MessageClient $messageClient)
    {
        if (!$this->messageClients->contains($messageClient)) {
            $this->messageClients->add($messageClient);
        }
    }

    public function setMessageClients($items)
    {
        if ($items instanceof ArrayCollection || is_array($items)) {
            foreach ($items as $item) {
                $this->addMessageClient($item);
            }
        } elseif ($items instanceof MessageClient) {
            $this->addMessageClient($items);
        } else {
            throw new \Exception("$items must be an instance of MessageClient or ArrayCollection");
        }
    }

    /**
     * Get ArrayCollection
     *
     * @return ArrayCollection $messageClients
     */
    public function getMessageClients()
    {
        return $this->messageClients;
    }

    /**
     * @return bool
     */
    public function isIsClientMeti()
    {
        return $this->isClientMeti;
    }

    /**
     * @param bool $isClientMeti
     */
    public function setIsClientMeti($isClientMeti)
    {
        $this->isClientMeti = $isClientMeti;
    }

    /**
     * @return bool
     */
    public function isIsRecipient()
    {
        return $this->isRecipient;
    }

    /**
     * @param bool $isRecipient
     */
    public function setIsRecipient($isRecipient)
    {
        $this->isRecipient = $isRecipient;
    }

}