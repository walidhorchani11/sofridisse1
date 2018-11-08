<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Enseigne
 *
 * @ORM\Table(name="enseigne", indexes={@ORM\Index(name="code_idx", columns={"code_enseigne"})})
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\EnseigneRepository")
 */
class Enseigne
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_enseigne", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Promotion", mappedBy="enseigne")
     */
    private $promotions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Tarif", mappedBy="enseigne")
     */
    private $tarifs;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Client", mappedBy="enseigne")
     */
    private $clients;

    /**
     * @var string
     *
     * @ORM\Column(name="for_prospect", type="boolean", nullable=true)
     */
    private $for_prospect;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->clients = New ArrayCollection();
        $this->promotions = New ArrayCollection();
        $this->tarifs = New ArrayCollection();
        $this->createdAt = new \DateTime('NOW');
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
    public function getPromotions()
    {
        return $this->promotions;
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
     * @return ArrayCollection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
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
     * @return boolean
     */
    public function getForProspect()
    {
        return $this->for_prospect;
    }

    /**
     * @param boolean $for_prospect
     */
    public function setForProspect($for_prospect)
    {
        $this->for_prospect = $for_prospect;
    }

}