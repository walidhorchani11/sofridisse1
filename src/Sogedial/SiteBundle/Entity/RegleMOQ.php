<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * RegleMOQ
 *
 * @ORM\Table(name="regle_moq")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\RegleMOQRepository")
 */
class RegleMOQ
{
    /**
     * @ORM\Id
     * @ORM\Column(name="code_regle_moq", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="regle_moq_quantite", type="integer")
     */
    private $quantiteMinimale;

    /**
     * @var string
     *
     * @ORM\Column(name="regle_moq_unite", type="string", length=60)
     */
    private $unite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="regle_moq_group", type="boolean")
     */

    private $group;
    /**
     * @var boolean
     *
     * @ORM\Column(name="regle_moq_mix", type="boolean")
     */
    private $mix;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Supplier", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="code_supplier", nullable=true, referencedColumnName="code_supplier")
     */
    private $supplier;

    /**
     * ProduitClient constructor.
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
     * @return integer
     */
    public function getQuantiteMinimale()
    {
        return $this->quantiteMinimale;
    }

    /**
     * @param integer $quantiteMinimale
     */
    public function setQuantiteMinimale($quantiteMinimale)
    {
        $this->quantiteMinimale = $quantiteMinimale;
    }

    /**
     * @return boolean
     */
    public function getMix()
    {
        return $this->mix;
    }

    /**
     * @param boolean $typeMoq
     */
    public function setMix($mix)
    {
        $this->mix = $mix;
    }

    /**
     * @return boolean
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param boolean $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return integer
     */
    public function getUnite()
    {
        return $this->unite;
    }

    /**
     * @param string $unite
     */
    public function setUnite($unite)
    {
        $this->unite = $unite;
    }

    /**
     * @return string
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @param string $suppliers
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    }
}