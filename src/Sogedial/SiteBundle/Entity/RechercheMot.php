<?php

namespace Sogedial\SiteBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RechercheMot
 *
 * @ORM\Table(name="recherche_mot", uniqueConstraints={@ORM\UniqueConstraint(name="phonetique_provenance", columns={"phonetique", "provenance"})})
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\RechercheMotRepository")
 */
class RechercheMot
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_recherche_mot", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    private $id_recherche_mot;

    /**
     * @var string
     *
     * @ORM\Column(name="phonetique", type="string", length=40, nullable=false)
     */
    private $phonetique;

    /**
     * @var integer
     *
     * @ORM\Column(name="provenance", type="integer", nullable=false)
     */
    private $provenance;

    /**
     * @var ArrayCollection Produit $produits
     * Owning Side
     *
     * @ORM\ManyToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="recherche_mots", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"})
     * @ORM\JoinTable(name="produit_recherche_mot",
     *   joinColumns={@ORM\JoinColumn(name="id_recherche_mot", referencedColumnName="id_recherche_mot")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")}
     * )
     */
    private $produits;

    public function __construct($phonetique, $provenance)
    {
        $this->produits = new ArrayCollection();
        $this->setPhonetique($phonetique);
        $this->setProvenance($provenance);
    }

    /**
     * @return integer
     */
    public function getIdRechercheMot()
    {
        return $this->id_recherche_mot;
    }

    /**
     * @return string
     */
    public function getPhonetique()
    {
        return $this->phonetique;
    }

    /**
     * @param string $phonetique
     */
    public function setPhonetique($phonetique)
    {
        $this->phonetique = $phonetique;
    }

    /**
     * @return integer
     */
    public function getProvenance()
    {
        return $this->provenance;
    }

    /**
     * @param integer $provenance
     */
    public function setProvenance($provenance)
    {
        $this->provenance = $provenance;
    }

    /**
     * Add produits
     *
     * @param Produit $produits
     * @return RechercheMot
     */
    public function addProduit(Produit $produits)
    {
        // cette fonction est appelée par entité Produit lors de la création du lien bi-directionnelle _depuis_ le produit
        // ne pas appeler directement!

        $this->produits[] = $produits;

        return $this;
    }

    /**
     * Remove produits
     *
     * @param Produit $produits
     */
    public function removeProduit(Produit $produits)
    {
        $this->produits->removeElement($produits);
    }

    /**
     * Get produits
     *
     * @return ArrayCollection
     */
    public function getProduits()
    {
        return $this->produits;
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

}