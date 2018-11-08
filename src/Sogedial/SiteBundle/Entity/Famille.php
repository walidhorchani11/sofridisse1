<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Famille
 *
 * @ORM\Table(name="famille")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\FamilleRepository")
 */
class Famille
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_famille", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_famille_en", type="string", length=255, nullable=true)
     */
    private $libelleEn;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="famille")
     */
    private $produits;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\UserBundle\Entity\FamilySelection",  mappedBy="entity", cascade={"persist"})
     */
    private $selections;

    /**
     * @var Rayon
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Rayon", inversedBy="familles")
     * @ORM\JoinColumn(name="code_rayon", referencedColumnName="code_rayon")
     */
    private $rayon;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\SousFamille",  mappedBy="famille", fetch="EXTRA_LAZY")
     */
    private $sousFamilles;


    /**
     * @return string
     */
    public function getLibelleEn()
    {
        return $this->libelleEn;
    }

    /**
     * @param string $libelleEn
     */
    public function setLibelleEn($libelleEn)
    {
        $this->libelleEn = $libelleEn;
    }

    /**
     * Get libelle
     *
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
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->sousFamilles = new ArrayCollection();
        $this->produits = new ArrayCollection();
    }

    /**
     * Add produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     * @return Famille
     */
    public function addProduit(\Sogedial\SiteBundle\Entity\Produit $produits)
    {
        $this->produits[] = $produits;

        return $this;
    }

    /**
     * Remove produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     */
    public function removeProduit(\Sogedial\SiteBundle\Entity\Produit $produits)
    {
        $this->produits->removeElement($produits);
    }

    /**
     * Get produits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProduits()
    {
        return $this->produits;
    }

    /**
     * Add selections
     *
     * @param \Sogedial\UserBundle\Entity\FamilySelection $selections
     * @return Famille
     */
    public function addSelection(\Sogedial\UserBundle\Entity\FamilySelection $selections)
    {
        $this->selections[] = $selections;
        $selections->setEntity($this);

        return $this;
    }

    /**
     * Remove selections
     *
     * @param \Sogedial\UserBundle\Entity\FamilySelection $selections
     */
    public function removeSelection(\Sogedial\UserBundle\Entity\FamilySelection $selections)
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
     * Get rayon
     *
     * @return Rayon
     */
    public function getRayon()
    {
        return $this->rayon;
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
     * Add sousFamilles
     *
     * @param SousFamille $sousFamilles
     * @return $this
     */
    public function addSousFamille(SousFamille $sousFamilles)
    {
        $this->sousFamilles[] = $sousFamilles;

        return $this;
    }

    /**
     * Remove sousFamilles
     *
     * @param SousFamille $sousFamilles
     */
    public function removeSousFamille(SousFamille $sousFamilles)
    {
        $this->sousFamilles->removeElement($sousFamilles);
    }

    /**
     * Get sousFamilles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSousFamilles()
    {
        return $this->sousFamilles;
    }
}
