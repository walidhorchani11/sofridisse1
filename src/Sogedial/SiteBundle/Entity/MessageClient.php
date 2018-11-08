<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use \Datetime;

/**
 * MessageClient
 *
 * @ORM\Table(name="message_client")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\MessageClientRepository")
 */
class MessageClient
{

    /**
     * @var integer
     * 
     * @ORM\Column(name="id", type="integer", nullable=false, unique=true) @ORM\GeneratedValue
     * @ORM\Id 
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    private $libelle;

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
     * @var boolean
     *
     * @ORM\Column(name="e_actif", type="boolean", nullable=true, options={"default":1})
     */
    private $e_actif;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=350, nullable=true)
     */
    private $text;


    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="clients", fetch="EAGER")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

  /**
     * @var ArrayCollection MessageClient $clients
     *
     * Inverse side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\Client", mappedBy="messageClients", fetch="EXTRA_LAZY")
     */
    private $clients;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        $this->dateDebutValidite = new Datetime();
        $this->clients = new ArrayCollection();
    }

    /**
    * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }


    /**
     * Set entreprise
     *
     * @param Entreprise $entreprise
     * @return Assortiment
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
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
     * Add Client
     *
     * @param Client $client
     */
    public function addClient(Client $client)
    {
        if (!$this->clients->contains($client)) {
            if (!$client->getMessageClients()->contains($this)) {
                $client->setMessageClients($this);
            }
            $this->clients->add($client);
        }
    }

    public function setClients($items)
    {
        if ($items instanceof ArrayCollection || is_array($items)) {
            foreach ($items as $item) {
                $this->addClient($item);
            }
        } elseif ($items instanceof Client) {
            $this->addClient($items);
        } else {
            throw new \Exception("$items must be an instance of Client or ArrayCollection");
        }
    }

    /**
     * Get ArrayCollection
     *
     * @return ArrayCollection $clients
     */
    public function getClients()
    {
        return $this->clients;
    }

}