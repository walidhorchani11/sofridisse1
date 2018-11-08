<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FamilleRHF
 *
 * @ORM\Table(name="fam_rhf")
 * @ORM\Entity
 */
class FamilleRHF
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id_fam_rhf", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="val_fam_rhf", type="string", length=255)
     */
    private $valeur;

    /**
     * @var string
     *
     * @ORM\Column(name="lib_fam_rhf", type="string", length=255)
     */
    private $libelle;

    /**
     * @var string
     *
     * @ORM\Column(name="dpt_fam_rhf", type="string", length=255)
     */
    private $departement;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Produit", mappedBy="familleRHF")
     */
    private $produits;

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
     * Set valeur
     *
     * @param string $valeur
     * @return FamilleRHF
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;

        return $this;
    }

    /**
     * Get valeur
     *
     * @return string
     */
    public function getValeur()
    {
        return $this->valeur;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return FamilleRHF
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;

        return $this;
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
     * Set departement
     *
     * @param string $departement
     * @return FamilleRHF
     */
    public function setDepartement($departement)
    {
        $this->departement = $departement;

        return $this;
    }

    /**
     * Get departement
     *
     * @return string
     */
    public function getDepartement()
    {
        return $this->departement;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->produits = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add produits
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produits
     * @return FamilleRHF
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
}
