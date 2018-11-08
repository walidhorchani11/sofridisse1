<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Nutrition
 *
 * @ORM\Table(name="nutrition")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\NutritionRepository")
 */
class Nutrition
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
     * @var string
     *
     * @ORM\Column(name="calories", type="string", length=255)
     */
    private $calories;

    /**
     * @var string
     *
     * @ORM\Column(name="joules", type="string", length=255)
     */
    private $joules;

    /**
     * @var string
     *
     * @ORM\Column(name="lipides", type="string", length=255)
     */
    private $lipides;

    /**
     * @var string
     *
     * @ORM\Column(name="glucides", type="string", length=255)
     */
    private $glucides;

    /**
     * @var string
     *
     * @ORM\Column(name="proteines", type="string", length=255)
     */
    private $proteines;

    /**
     * @ORM\OneToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", inversedBy="nutrition")
     * @ORM\JoinColumn(name="code_produit", nullable=true, referencedColumnName="code_produit")
     */
    private $produit;

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
     * Set calories
     *
     * @param string $calories
     * @return Nutrition
     */
    public function setCalories($calories)
    {
        $this->calories = $calories;

        return $this;
    }

    /**
     * Get calories
     *
     * @return string
     */
    public function getCalories()
    {
        return $this->calories;
    }

    /**
     * Set joules
     *
     * @param string $joules
     * @return Nutrition
     */
    public function setJoules($joules)
    {
        $this->joules = $joules;

        return $this;
    }

    /**
     * Get joules
     *
     * @return string
     */
    public function getJoules()
    {
        return $this->joules;
    }

    /**
     * Set lipides
     *
     * @param string $lipides
     * @return Nutrition
     */
    public function setLipides($lipides)
    {
        $this->lipides = $lipides;

        return $this;
    }

    /**
     * Get lipides
     *
     * @return string
     */
    public function getLipides()
    {
        return $this->lipides;
    }

    /**
     * Set glucides
     *
     * @param string $glucides
     * @return Nutrition
     */
    public function setGlucides($glucides)
    {
        $this->glucides = $glucides;

        return $this;
    }

    /**
     * Get glucides
     *
     * @return string
     */
    public function getGlucides()
    {
        return $this->glucides;
    }

    /**
     * Set proteines
     *
     * @param string $proteines
     * @return Nutrition
     */
    public function setProteines($proteines)
    {
        $this->proteines = $proteines;

        return $this;
    }

    /**
     * Get proteines
     *
     * @return string
     */
    public function getProteines()
    {
        return $this->proteines;
    }


    /**
     * Set produit
     *
     * @param \Sogedial\SiteBundle\Entity\Produit $produit
     * @return Nutrition
     */
    public function setProduit(\Sogedial\SiteBundle\Entity\Produit $produit = null)
    {
        $this->produit = $produit;

        return $this;
    }

    /**
     * Get produit
     *
     * @return \Sogedial\SiteBundle\Entity\Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }
}
