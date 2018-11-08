<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProduitCompteur
 *
 * @ORM\Table(name="produits_compteur")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ProduitCompteur")
 */
class ProduitCompteur
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=11, nullable=false)
     * @ORM\Id
     */
    private $id;

     /**
     * @var string
     *
     * @ORM\Column(name="valeur_assortiment", type="string", length=11, nullable=false)
     */
    private $valeur;

     /**
     * @var string
     *
     * @ORM\Column(name="code_tarification", type="string", length=11, nullable=false)
     */
    private $tarif;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="produits_compteur")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_objet", type="integer", nullable=false)
     */
    private $objetType;

    /**
     * @var string
     *
     * @ORM\Column(name="objet", type="string", length=11, nullable=false)
     */
    private $objet;

    /**
    * @var integer
    *
    * @ORM\Column(name="general_compteur", type="integer", nullable=true)
    */
    private $general;

    /**
    * @var integer
    *
    * @ORM\Column(name="nouveautes_compteur", type="integer", nullable=true)
    */
    private $nouveautes;

    /**
    * @var integer
    *
    * @ORM\Column(name="precommande_avion_compteur", type="integer", nullable=true)
    */
    private $preCommandeAvion;

    /**
    * @var integer
    *
    * @ORM\Column(name="precommande_bateau_compteur", type="integer", nullable=true)
    */
    private $preCommandeBateau;

    public function __construct()
    {
        $this->compteurs = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;        
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getValeur()
    {
        return $this->valeur;
    }

    /**
     * @param string $valeur
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;
    }

    /**
     * @return string
     */
    public function getTarif()
    {
        return $this->tarif;
    }

    /**
     * @param string $tarif
     */
    public function setTarif($tarif)
    {
        $this->tarif = $tarif;
    }

    /**
     * Set entreprise
     *
     * @param Entreprise $entreprise
     */
    public function setEntreprise(Entreprise $entreprise)
    {
        $this->entreprise = $entreprise;
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
     * @return string
     */
    public function getObjetType()
    {
        return $this->objetType;
    }

    /**
     * @param string $valeur
     */
    public function setObjetType($objetType)
    {
        $this->objetType = $objetType;
    }

    /**
     * @return string
     */
    public function getObjet()
    {
        return $this->objet;
    }

    /**
     * @param string $valeur
     */
    public function setObjet($objet)
    {
        $this->objet = $objet;
    }

    /**
     * @return int
     */
    public function getGeneralCompteur()
    {
        return $this->catalogue;
    }

    /**
     * @param int $catalogue
     */
    public function setGeneralCompteur($general)
    {
        $this->general = $general;
    }

    /**
     * @return int
     */
    public function getNouveautesCompteur()
    {
        return $this->nouveautes;
    }

    /**
     * @param int $nouveautes
     */
    public function setNouveautesCompteur($nouveautes)
    {
        $this->nouveautes = $nouveautes;
    }

    /**
     * @return int
     */
    public function getPreCommandeAvion()
    {
        return $this->preCommandeAvion;
    }

    /**
     * @param int $preCommandeAvion
     */
    public function setPreCommandeAvion($preCommandeAvion)
    {
        $this->preCommandeAvion = $preCommandeAvion;
    }

    /**
     * @return int
     */
    public function getPreCommandeBateau()
    {
        return $this->preCommandeBateau;
    }

    /**
     * @param int $preCommandeBateau
     */
    public function setPreCommandeBateau($preCommandeBateau)
    {
        $this->preCommandeBateau = $preCommandeBateau;
    }

}