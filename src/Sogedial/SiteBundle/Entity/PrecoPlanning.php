<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;


/**
 * PrecoPlanning
 *
 * @ORM\Table(name="preco_planning")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\PrecoPlanningRepository")
 */
class PrecoPlanning
{
    /**
     * @var $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var $dateDebutCommande
     *
     * @ORM\Column(name="date_debut_commande", type="datetime", nullable=false)
     */
    private $dateDebutCommande;

    /**
     * @var $dateFinCommande
     *
     * @ORM\Column(name="date_fin_commande", type="datetime", nullable=false)
     */
    private $dateFinCommande;

    /**
     * @var $dateLivraison
     *
     * @ORM\Column(name="date_livraison", type="datetime", nullable=false)
     */
    private $dateLivraison;

    /**
     * @var $identifiantSociete
     *
     * @ORM\Column( name="identifiant_societe", type="integer", nullable=true )
     */
    private $identifiantSociete;

    /**
     * @var $libelleSociete
     *
     * @ORM\Column(name="libelle_societe", type="string", length=60, nullable=true)
     */
    private $libelleSociete;

    /**
     * @var $annee
     *
     * @ORM\Column(name="annee", type="string", length=4, nullable=true)
     */
    private $annee;

    /**
     * @var $typePreco
     *
     * @ORM\Column(name="type_preco", type="string", length=55, nullable=true)
     */
    private $typePreco;

    /**
     * @var $codePreco
     *
     * @ORM\Column(name="code_preco", type="string", length=4, nullable=true)
     */
    private $codePreco;

    /**
     * @var $dateIso
     *
     * @ORM\Column( name="date_iso", type="integer", nullable=true )
     */
    private $dateIso;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDateDebutCommande()
    {
        return $this->dateDebutCommande;
    }

    /**
     * @param mixed $dateDebutCommande
     */
    public function setDateDebutCommande($dateDebutCommande)
    {
        $this->dateDebutCommande = $dateDebutCommande;
    }

    /**
     * @return mixed
     */
    public function getDateFinCommande()
    {
        return $this->dateFinCommande;
    }

    /**
     * @param mixed $dateFinCommande
     */
    public function setDateFinCommande($dateFinCommande)
    {
        $this->dateFinCommande = $dateFinCommande;
    }

    /**
     * @return mixed
     */
    public function getDateLivraison()
    {
        return $this->dateLivraison;
    }

    /**
     * @param mixed $dateLivraison
     */
    public function setDateLivraison($dateLivraison)
    {
        $this->dateLivraison = $dateLivraison;
    }

    /**
     * @return mixed
     */
    public function getIdentifiantSociete()
    {
        return $this->identifiantSociete;
    }

    /**
     * @param mixed $identifiantSociete
     */
    public function setIdentifiantSociete($identifiantSociete)
    {
        $this->identifiantSociete = $identifiantSociete;
    }

    /**
     * @return mixed
     */
    public function getLibelleSociete()
    {
        return $this->libelleSociete;
    }

    /**
     * @param mixed $libelleSociete
     */
    public function setLibelleSociete($libelleSociete)
    {
        $this->libelleSociete = $libelleSociete;
    }

    /**
     * @return mixed
     */
    public function getAnnee()
    {
        return $this->annee;
    }

    /**
     * @param mixed $annee
     */
    public function setAnnee($annee)
    {
        $this->annee = $annee;
    }

    /**
     * @return mixed
     */
    public function getTypePreco()
    {
        return $this->typePreco;
    }

    /**
     * @param mixed $typePreco
     */
    public function setTypePreco($typePreco)
    {
        $this->typePreco = $typePreco;
    }

    /**
     * @return mixed
     */
    public function getCodePreco()
    {
        return $this->codePreco;
    }

    /**
     * @param mixed $codePreco
     */
    public function setCodePreco($codePreco)
    {
        $this->codePreco = $codePreco;
    }

    /**
     * @return mixed
     */
    public function getDateIso()
    {
        return $this->dateIso;
    }

    /**
     * @param mixed $dateIso
     */
    public function setDateIso($dateIso)
    {
        $this->dateIso = $dateIso;
    }


}