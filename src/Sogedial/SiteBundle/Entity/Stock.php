<?php


namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Stock
 *
 * @ORM\Table(name="stock")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\StockRepository")
 */
class Stock
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_stock", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="stock_theorique_colis", type="string", length=255)
     */
    private $stockTheoriqueColis;

    /**
     * @var string
     *
     * @ORM\Column(name="stock_theorique_uc", type="string", length=255)
     */
    private $stockTheoriqueUc;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="stocks")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="stocks")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="stock", cascade={"persist"})
     * @ORM\JoinColumn(name="code_produit", nullable=true, referencedColumnName="code_produit")
     */
    private $produit;

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
     * @return string
     */
    public function getStockTheoriqueColis()
    {
        return $this->stockTheoriqueColis;
    }

    /**
     * @return string
     */
    public function getStockTheoriqueUc()
    {
        return $this->stockTheoriqueUc;
    }

    /**
     * @param string $stockTheoriqueColis
     */
    public function setStockTheoriqueColis($stockTheoriqueColis)
    {
        $this->stockTheoriqueColis = $stockTheoriqueColis;
    }

    /**
     * @param string $stockTheoriqueUc
     */
    public function setStockTheoriqueUc($stockTheoriqueUc)
    {
        $this->stockTheoriqueUc = $stockTheoriqueUc;
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
     * @return Stock
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
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
     * @return Stock
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

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
     * Set produit
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produit
     * @return Colis
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit = null)
    {
        $this->produit = $produit;

        return $this;
    }

    /**
     * Get produit
     *
     * @return \Sogedial\SiteBundle\Entity\Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }
}