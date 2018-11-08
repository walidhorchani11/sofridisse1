<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sogedial\SiteBundle\Entity\LigneCommande;
use Sogedial\UserBundle\Entity\User;

/**
 * HistoriqueLigneCommande
 *
 * @ORM\Table(name="historique_ligne_commande")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\HistoriqueLigneCommandeRepository")
 */
class HistoriqueLigneCommande
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\LigneCommande", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="ligne_commande_id", referencedColumnName="id", nullable=FALSE)
     */
    private $ligneCommande;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id_utilisateur")
     */
    private $modifier;

    /**
     * @var int
     *
     * @ORM\Column(name="quantite", type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLigneCommande()
    {
        return $this->ligneCommande;
    }

    public function setLigneCommande(LigneCommande $ligneCommande)
    {
        $this->ligneCommande = $ligneCommande;
        return $this;
    }

    /**
     * Set modifier
     *
     * @param \Sogedial\UserBundle\Entity\User $modifier
     * @return $this
     */
    public function setModifier(User $modifier)
    {
        $this->modifier = $modifier;
        return $this;
    }

    /**
     * Get modifier
     *
     * @return \Sogedial\UserBundle\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * @return int
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * @param int $quantite
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;
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
}