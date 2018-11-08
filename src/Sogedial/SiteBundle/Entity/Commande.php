<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Commande
 *
 * @ORM\Table(name="commande", indexes={@ORM\Index(name="commande_idx", columns={"id"})})
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\CommandeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Commande
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
     * @ORM\ManyToOne(targetEntity="Sogedial\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client")
     * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="validator_id", referencedColumnName="id_utilisateur")
     */
    private $validator;

    /**
     * @var string
     *
     * @ORM\Column(name="numero", type="string", length=255, nullable=true)
     */
    private $numero;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\LigneCommande", mappedBy="commande", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    private $lignes;

    /**
     * @var string
     *
     * @ORM\Column(name="date_modification", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="delivery_date", type="datetime", nullable=true)
     */
    private $deliveryDate;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="validating_date", type="datetime", nullable=true)
     */
    private $validatingDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent", type="integer", nullable=true)
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature_commande", type="string", length=64, nullable=true)
     */
    private $temperatureCommande;

    /**
     * @var decimal
     *
     * @ORM\Column(name="montant_commande", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $montantCommande;

    /**
     * @var string
     *
     * @ORM\Column(name="application_origine", type="string", length=10,  nullable=true)
     */
    private $applicationOrigine;

    /**
     * @var integer
     *
     * @ORM\Column(name="demande_colis", type="integer", nullable=true)
     */
    private $demandeColis;

    /**
     * @var string
     *
     * @ORM\Column(name="commentaire_client", type="string", length=40, nullable=true)
     */
    private $commentaire;

    /**
     * @var boolean
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="code_precommande", referencedColumnName="code_entreprise", nullable=true)
     */
    private $codePrecommande;

    /**
     * @var string
     *
     * @ORM\Column(name="poids_commande", type="string", length=255, nullable=true)
     */
    private $poidsCommande;

    /**
     * @var string
     *
     * @ORM\Column(name="volume_commande", type="string", length=255, nullable=true)
     */
    private $volumeCommande;

    /**
    * @var boolean
    * @ORM\Column(name="recept_pdf", type="boolean", nullable=true, options={"default" = false})
    */
    private $receptPDF;

    /**
    * @var boolean
    * @ORM\Column(name="recept_email", type="boolean", nullable=true, options={"default" = false})
    */
    private $receptEmail;

    /**
     * @var boolean
     * @ORM\Column(name="recept_client_xlsx", type="boolean", nullable=true, options={"default" = false})
     */
    private $receptClientXlsx;

    /**
     * @var boolean
     * @ORM\Column(name="recept_client_email", type="boolean", nullable=true, options={"default" = false})
     */
    private $receptClientEmail;

    /**
     * @var boolean
     * @ORM\Column(name="dates_string", type="string", nullable=true, length=60 )
     */
    private $datesString;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->createdAt = new \DateTime('now');
        $this->parent = null;
        $this->receptEmail = false;
        $this->receptPDF = false;
        $this->receptClientEmail = false;
        $this->receptClientXlsx = false;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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

    /**
     * Set user
     *
     * @param \Sogedial\SiteBundle\Entity\Entreprise $entreprise
     * @return $this
     */
    public function setEntreprise(\Sogedial\SiteBundle\Entity\Entreprise $entreprise = null)
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
     * Set user
     *
     * @param \Sogedial\UserBundle\Entity\User $user
     * @return $this
     */
    public function setUser(\Sogedial\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Sogedial\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

        /**
     * Set validator
     *
     * @param \Sogedial\UserBundle\Entity\User $validator
     * @return $this
     */
    public function setValidator( $validator = null)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Get validator
     *
     * @return integer
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Get client
     *
     * @return \Sogedial\SiteBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client
     *
     * @param \Sogedial\SiteBundle\Entity\Client $client
     * @return $this
     */
    public function setClient(\Sogedial\SiteBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }


    /**
     * @ORM\PostPersist()
     */
    public function setGenereatedNumber()
    {
        $this->setNumero(sprintf("%09d", $this->id));       // seulement 8 sont utilisés. on laisse 9 pour ne pas casser l'ordre alphabetique par rapport aux commandes existantes...
    }

    /**
     * @return string
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * @param string $numero
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;
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

    public function getLignes()
    {
        return $this->lignes->toArray();
    }

    public function addLigne(LigneCommande $ligne)
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function removeLigne(LigneCommande $ligne)
    {
        if ($this->lignes->contains($ligne)) {
            $this->lignes->removeElement($ligne);
            $ligne->setCommande(null);
        }

        return $this;
    }

    public function getProduits()
    {
        return array_map(
            function ($ligne) {
                return $ligne->getProduit();
            },
            $this->lignes->toArray()
        );
    }

    /**
     * @return Datetime
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param Datetime $deliveryDate
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $deliveryDate;
    }

    /**
     * @return Datetime
     */
    public function getValidatingDate()
    {
        return $this->validatingDate;
    }

    /**
     * @param Datetime $validatingDate
     */
    public function setValidatingDate($validatingDate)
    {
        $this->validatingDate = $validatingDate;
    }

    /**
     * @return string
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * @param string $commentaire
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;
    }

    /**
     * @return int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param int $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getTemperatureCommande()
    {
        return $this->temperatureCommande;
    }

    /**
     * @param string $temperatureCommande
     */
    public function setTemperatureCommande($temperatureCommande)
    {
        $this->temperatureCommande = $temperatureCommande;
    }

    /**
     * @return integer $demandeColis
     */
    public function getDemandeColis()
    {
        return $this->demandeColis;
    }

    /**
     * @param integer $demandeColis
     */
    public function setDemandeColis($demandeColis)
    {
        $this->demandeColis = $demandeColis;
    }

    public function getApplicationOrigine()
    {
        return $this->applicationOrigine;
    }

    public function setApplicationOrigine($applicationOrigine)
    {
        $this->applicationOrigine = $applicationOrigine;
    }

    /*
    public function getMontantFacturation()
    {
        return $this->montantFacturation;
    }

    public function setMontantFacturation($montantFacturation)
    {
        $this->montantFacturation = $montantFacturation;
    }
    */

    /**
    * @return montantCommande decimal
    */
    public function getMontantCommande()
    {
        return $this->montantCommande;
    }

    /**
    * @param montantCommande decimal
    */
    public function setMontantCommande($montantCommande)
    {
        $this->montantCommande = $montantCommande;
    }

    /**
    * @return {Entreprise} codePrecommande
    */
    public function getCodePrecommande()
    {
        return $this->codePrecommande;
    }

    /**
    * @param {Entreprise} codePrecommande
    */
    public function setCodePrecommande(Entreprise $codePrecommande)
    {
        $this->codePrecommande = $codePrecommande;
    }

    /**
     * @return string
    */
    public function getVolumeCommande()
    {
        return $this->volumeCommande;
    }

    /**
     * @param string $volumeCommande
     */
    public function setVolumeCommande($volumeCommande)
    {
        $this->volumeCommande = $volumeCommande;
    }

    /**
     * @return string
     */
    public function getPoidsCommande()
    {
        return $this->poidsCommande;
    }

    /**
     * @param string $poidsCommande
     */
    public function setPoidsCommande($poidsCommande)
    {
        $this->poidsCommande = $poidsCommande;
    }

    public function getReceptPDF(){
        return $this->receptPDF;
    }

    public function setReceptPDF($pdfStatus){
        $this->receptPDF = $pdfStatus;

        return $this;
    }

    public function getReceptEmail(){
        return $this->receptEmail;
    }

    public function setReceptEmail($receptEmail){
        $this->receptEmail = $receptEmail;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDatesString()
    {
        return $this->datesString;
    }

    /**
     * @param bool $datesString
     */
    public function setDatesString($datesString)
    {
        $this->datesString = $datesString;
    }

    /**
     * @return bool
     */
    public function isReceptClientXlsx()
    {
        return $this->receptClientXlsx;
    }

    /**
     * @param bool $receptClientXlsx
     */
    public function setReceptClientXlsx($receptClientXlsx)
    {
        $this->receptClientXlsx = $receptClientXlsx;
    }

    /**
     * @return bool
     */
    public function isReceptClientEmail()
    {
        return $this->receptClientEmail;
    }

    /**
     * @param bool $receptClientEmail
     */
    public function setReceptClientEmail($receptClientEmail)
    {
        $this->receptClientEmail = $receptClientEmail;
    }

}
