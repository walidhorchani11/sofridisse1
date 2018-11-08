<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientMeti
 *
 * @ORM\Table(name="client_meti")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ClientMetiRepository")
 */

class ClientMeti
{
        
    /**
     * @var string
     *
     * @ORM\Column(name="code_client", type="string", length=55, unique=true, nullable=false)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="code_client_meti", type="string", length=55, unique=true, nullable=false)
     */
    private $codeMeti;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_site",  type="string", length=128, nullable=false)
     */
    private $libelleSite;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Region")
     * @ORM\JoinColumn(name="code_region", referencedColumnName="code_region")
     */
    private $region;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_safo", type="boolean", nullable=true, options={"default":0})
     */
    private $is_safo;

    /**
     * @var Enseigne
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Enseigne")
     * @ORM\JoinColumn(name="code_enseigne", referencedColumnName="code_enseigne")
     */
    private $enseigne;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle_enseigne",  type="string", length=128, nullable=false)
     */
    private $libelleEnseigne;

    /**
     * @var string
     *
     * @ORM\Column(name="code_client_as400",  type="string", length=55, nullable=false)
     */
    private $clientAs400;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_referencement",  type="string", length=255, nullable=true)
     */
     private $mailReferencement; //TODO: un jour ce mail devra être obligatoire avec un nullable à false

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
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
    public function getCodeMeti()
    {
        return $this->codeMeti;
    }

    /**
     * @param string $code
     */
    public function setCodeMeti($codeMeti)
    {
        $this->codeMeti = $codeMeti;
    }

    /**
     * @return string
     */
    public function getLibelleSite()
    {
        return $this->libelleSite;
    }

    /**
     * @param string $code
     */
    public function setLibelleSite($libelleSite)
    {
        $this->libelleSite = $libelleSite;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return Promotion
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSafo()
    {
        return $this->is_safo;
    }

    /**
     * @param bool $is_safo
     */
    public function setIsSafo($is_safo)
    {
        $this->is_safo = $is_safo;
    }

    /**
     * Set enseigne
     *
     * @param Enseigne|null $enseigne
     * @return $this
     */
    public function setEnseigne(Enseigne $enseigne = null)
    {
        $this->enseigne = $enseigne;

        return $this;
    }

    /**
     * get enseigne
     *
     * @return Enseigne
     */
    public function getEnseigne()
    {
        return $this->enseigne;
    }

    /**
     * @return string
     */
    public function getLibelleEnseigne()
    {
        return $this->libelleEnseigne;
    }

    /**
     * @param string $code
     */
    public function setLibelleEnseigne($libelleEnseigne)
    {
        $this->libelleEnseigne = $libelleEnseigne;
    }

    /**
     * @return string
     */
    public function getClientAs400()
    {
        return $this->clientAs400;
    }

    /**
     * @param string $code
     */
    public function setClientAs400($clientAs400)
    {
        $this->clientAs400 = $clientAs400;
    }

    /**
     * @return string
    */
     public function getMailReferencement()
     {
         return $this->mailReferencement;
     }

    /**
     * @param string $mailReferencement
    */
    public function setMailReferencement($mailReferencement)
    {
        $this->mailReferencement = $mailReferencement;
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

        /**
     * @return Datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
}