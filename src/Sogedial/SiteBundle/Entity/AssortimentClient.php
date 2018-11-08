<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Assortiment
 *
 * @ORM\Table(name="assortiment_client", uniqueConstraints={@UniqueConstraint(name="code_client_valeur_unique", columns={"code_client", "valeur"})})
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\AssortimentClientRepository")
 */
class AssortimentClient
{
    /**
    * @var integer
    *
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * @var nom
    *
    * @ORM\Column(name="assortiment_nom", type="string", length=25, nullable=true)
    */
    private $nom;

    /**
    * @var Client $clients
    *
    * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client")
    * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
    */
    private $client;

    /**
    * @var string
    *
    * @ORM\Column(name="valeur", type="string", length=11, nullable=false)
    */
    private $valeur;

    /**
     * @var Assortiment
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Assortiment")
     * @ORM\JoinColumn(name="code_assortiment", referencedColumnName="code_assortiment")
     */
    private $assortiment;

    /**
    * @var boolean
    *
    * @ORM\Column(name="as400_assortiment", type="boolean", nullable=true)
    */
    private $as400assortiment;

    /**
    * @var boolean
    *
    * @ORM\Column(name="assortiment_courant", type="boolean", nullable=true)
    */
    private $assortimentCourant;

    /**
    * Construct
    */
    public function __construct()
    {
        $this->assortimentCourant = false;
        $this->clients = new ArrayCollection();
    }


    /**
    * @return int
    */
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
    * @return string $client
    */
    public function getClient()
    {
        return $this->client;
    }

    /**
    * @param string $client
    */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param Assortiment $assortiment
     * @return $this
     */
    public function setAssortiment(Assortiment $assortiment = null)
    {
        $this->assortiment = $assortiment;

        return $this;
    }

    /**
     * @return Assortiment
     */
    public function getAssortiment()
    {
        return $this->assortiment;
    }

    /**
     * @param boolean as400assortiment
     * @return $this
     */
    public function setAs400assortiment($as400assortiment)
    {
        $this->as400assortiment = $as400assortiment;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAs400assortiment()
    {
        return $this->as400assortiment;
    }

    /**
     * @param boolean assortimentCourant
     * @return $this
     */
     public function setAssortimentCourant($assortimentCourant)
     {
         $this->assortimentCourant = $assortimentCourant;

         return $this;
     }

     /**
      * @return boolean
      */
     public function getAssortimentCourant()
     {
         return $this->assortimentCourant;
     }
}