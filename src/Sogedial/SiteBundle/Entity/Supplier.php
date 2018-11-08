<?php


namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Supplier
 *
 * @ORM\Table(name="supplier")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\SupplierRepository")
 */
class Supplier
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_supplier", type="string", length=22, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=125, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="appro", type="string", length=255, nullable=true)
     */
    private $appro;

    /**
     * @var string
     *
     * @ORM\Column(name="indicator11", type="string", length=1, nullable=true)
     */
    private $indicator11;

    /**
     * @var string
     *
     * @ORM\Column(name="original_code", type="string", length=255, nullable=true)
     */
    private $originalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="text", nullable=true)
     */
    private $frenqunecy;

    /**
     * @var string
     *
     * @ORM\Column(name="appro_delay", type="string", length=180, nullable=true)
     */
    private $approDelay;

    /**
     * @var ArrayCollection Produit $produits
     *
     * Owning side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", cascade={"persist"}, inversedBy="suppliers", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="produit_supplier",
     *   joinColumns={@ORM\JoinColumn(name="code_supplier", referencedColumnName="code_supplier")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")}
     * )
     */
    private $produits;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Promotion", mappedBy="supplier", fetch="EXTRA_LAZY")
     */
    private $promotions;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region", inversedBy="suppliers")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="suppliers")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->entreprise = NULL;
        $this->createdAt = new \DateTime('now');
        $this->produits = new ArrayCollection();
        $this->promotions = new ArrayCollection();
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
    public function getAppro()
    {
        return $this->appro;
    }

    /**
     * @return string
     */
    public function getIndicator11()
    {
        return $this->indicator11;
    }

    /**
     * @return string
     */
    public function getOriginalCode()
    {
        return $this->originalCode;
    }

    /**
     * @param string $originalCode
     */
    public function setOriginalCode($originalCode)
    {
        $this->originalCode = $originalCode;
    }

    /**
     * @return string
     */
    public function getFrenqunecy()
    {
        return $this->frenqunecy;
    }

    /**
     * @return string
     */
    public function getApproDelay()
    {
        return $this->approDelay;
    }

    /**
     * @param string $appro
     */
    public function setAppro($appro)
    {
        $this->appro = $appro;
    }

    /**
     * @param string $indicator11
     */
    public function setIndicator11($indicator11)
    {
        $this->indicator11 = $indicator11;
    }

    /**
     * @param string $frenqunecy
     */
    public function setFrenqunecy($frenqunecy)
    {
        $this->frenqunecy = $frenqunecy;
    }

    /**
     * @param string $approDelay
     */
    public function setApproDelay($approDelay)
    {
        $this->approDelay = $approDelay;
    }

    /**
     * Add Produit
     *
     * @param Produit $produit
     */
    public function addProduit(Produit $produit)
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
        }
    }

    public function setProduits($items)
    {
        if ($items instanceof ArrayCollection || is_array($items)) {
            foreach ($items as $item) {
                $this->addProduit($item);
            }
        } elseif ($items instanceof Produit) {
            $this->addProduit($items);
        } else {
            throw new \Exception("$items must be an instance of Produit or ArrayCollection");
        }
    }

    /**
     * Get ArrayCollection
     *
     * @return ArrayCollection $produits
     */
    public function getProduits()
    {
        return $this->produits;
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

}