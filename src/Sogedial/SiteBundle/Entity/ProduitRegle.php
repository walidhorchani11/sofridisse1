<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sogedial\SiteBundle\Entity\RegleMOQ;

/**
 * ProduitRegle
 *
 * @ORM\Table(name="produit_regle")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ProduitRegleRepository")
 */
class ProduitRegle
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id_regle_moq", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="quantiteMinimale")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\RegleMOQ", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="code_regle_moq", referencedColumnName="code_regle_moq")
     */
    private $regle;

    /**
     * ProduitRegle constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return Produit
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param Produit $code
     */
    public function setCode(Produit $code)
    {
        $this->code = $code;
    }

    /**
     * @return RegleMOQ
     */
    public function getRegle()
    {
        return $this->regle;
    }

    /**
     * @param Produit $regle
     */
    public function setRegle(RegleMOQ $regle)
    {
        $this->regle = $regle;
    }

}