<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Session
 *
 * @ORM\Table(name="region")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\RegionRepository")
 */
class Region
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_region", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", mappedBy="region", fetch="EXTRA_LAZY")
     */
    private $entreprises;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Tarif", mappedBy="region", fetch="EXTRA_LAZY")
     */
    private $tarifs;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Promotion", mappedBy="region", fetch="EXTRA_LAZY")
     */
    private $promotions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Assortiment", mappedBy="region", fetch="EXTRA_LAZY")
     */
    private $assortiments;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Stock", mappedBy="region", fetch="EXTRA_LAZY")
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
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Client", mappedBy="region", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $clients;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Supplier", mappedBy="region", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $suppliers;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->entreprises = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->assortiments = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->suppliers = new ArrayCollection();

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
     * @param string $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    /**
     * Add entreprises
     *
     * @param Entreprise $entreprises
     * @return $this
     */
    public function addEntreprise(Entreprise $entreprises)
    {
        $this->entreprises[] = $entreprises;

        return $this;
    }

    /**
     * Remove entreprises
     *
     * @param Entreprise $entreprises
     */
    public function removeEntreprise(Entreprise $entreprises)
    {
        $this->entreprises->removeElement($entreprises);
    }

    /**
     * Get entreprises
     *
     * @return ArrayCollection
     */
    public function getEntreprise()
    {
        return $this->entreprises;
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

}