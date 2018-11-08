<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Marque
 *
 * @ORM\Table(name="joursFeries")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\JoursFeriesRepository")
 */
class JoursFeries
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
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     *
     */
    private $libelle;

    /**
     * @var integer
     *
     * @ORM\Column(name="month_number", type="integer", length=2, nullable=true)
     *
     */
    private $monthNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="day_number", type="integer", length=2, nullable=true)
     *
     */
    private $dayNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", length=4, nullable=true)
     *
     */
    private $countryCode;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;


    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise", inversedBy="holidays")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * JoursFeries constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    /**
     * Get Entreprise
     *
     * @return Entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
    }

    /**
     * Set entreprise
     *
     * @param Entreprise $entreprise
     * @return JoursFeries
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
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
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return int
     */
    public function getMonthNumber()
    {
        return $this->monthNumber;
    }

    /**
     * @param int $monthNumber
     */
    public function setMonthNumber($monthNumber)
    {
        $this->monthNumber = $monthNumber;
    }

    /**
     * @return int
     */
    public function getDayNumber()
    {
        return $this->dayNumber;
    }

    /**
     * @param int $dayNumber
     */
    public function setDayNumber($dayNumber)
    {
        $this->dayNumber = $dayNumber;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }


}
