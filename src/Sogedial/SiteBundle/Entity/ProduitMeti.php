<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProduitMeti
 *
 * @ORM\Table(name="produit_meti")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ProduitMetiRepository")
 */

class ProduitMeti
{

     /**
     * @var string
     *
     * @ORM\Column(name="code_produit_meti", type="string", length=55, unique=true, nullable=false)
     * @ORM\Id
     */
    private $code;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client")
     * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
     */
    private $client;


    /**
     * @var Produit
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="promotions")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var ClientMeti
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\ClientMeti")
     * @ORM\JoinColumn(name="code_client_meti", referencedColumnName="code_client")
     */
    private $clientMeti;

    /**
     * @var string
     *
     * @ORM\Column(name="societe", type="string", length=11, nullable=true)
     */
    private $societe;

    /**
     * @var string
     *
     * @ORM\Column(name="produit_meti", type="string", length=55, nullable=false)
     */
    private $produitMeti;

    /**
     * @var string
     *
     * @ORM\Column(name="ean13_produit", type="string", length=13)
     */
    private $ean13;

    /**
     * @var integer
     *
     * @ORM\Column(name="stock", type="integer")
     */
    private $stock;
    
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
     * @return ClientMeti
     */
    public function getClientMeti()
    {
        return $this->clientMeti;
    }

    /**
     * @param ClientMeti $clientMeti
     */
    public function setClientMeti(ClientMeti $clientMeti = null)
    {
        $this->clientMeti = $clientMeti;
    }

    /**
     * @return string
     */
    public function getSociete()
    {
        return $this->societe;
    }

    /**
     * @param string $societe
     */
    public function setSociete($societe)
    {
        $this->societe = $societe;
    }

    /**
     * @return string
     */
    public function getProduitMeti()
    {
        return $this->produitMeti;
    }

    /**
     * @param string $produitMeti
     */
    public function setProduitMeti($produitMeti)
    {
        $this->produitMeti = $produitMeti;
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
    public function getStock()
    {
        return $this->sock;
    }

    /**
     * @param string $stock
     */
    public function setStock($stock)
    {
        $this->stock = $stock;
    }

}
