<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Bon de preparation
 *
 * @ORM\Table(name="bon_preparation")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\BonPreparationRepository")
 */
class BonPreparation
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=11, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var Commande
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Commande")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $commande;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="delivery_date", type="datetime")
     */
    private $deliveryDate;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="date_facturation", type="datetime", nullable=true)
     */
    private $dateFacturation;

    /**
     * @var string
     *
     * @ORM\Column(name="numero_facturation", type="string", length=10, nullable=true)
     */
    private $numeroFacturation;

    /**
     * @var decimal
     *
     * @ORM\Column(name="montant_facturation", type="decimal", precision=8, scale=2, nullable=true)
     */
    private $montantFacturation;

    /**
     * @var integer
     *
     * @ORM\Column(name="colis_facture", type="integer", nullable=true)
     */
    private $colisFacture;

    /**
     * @param command Command
     * @param numeroBP string
     */
    public function __construct($command, $codeBP)
    {
        $this->setCommande($command);
        $this->setCode($codeBP);
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
     * @return Commande
     */
    public function getCommande()
    {
        return $this->commande;
    }

    /**
     * @param Commande $commande
     */
    public function setCommande($commande)
    {
        $this->commande = $commande;
    }

    /**
    * @return Datetime deliveryDate
    */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
    * @param Datetime $deliveryDate
    */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $deliveryDate;
    }
 
    /**
    * @param Datetime $dateFacturation
    */
    public function getDateFacturation()
    {
        return $this->dateFacturation;
    }

    /**
    * @return Datetime dateFacturation
    */
    public function setDateFacturation($dateFacturation)
    {
        $this->dateFacturation = $dateFacturation;
    }

    /**
    * @return integer numeroFacturation
    */
    public function getNumeroFacturation()
    {
        return $this->numeroFacturation;
    }

    /**
    * @param integer numeroFacturation
    */
    public function setNumeroFacturation($numeroFacturation)
    {
        $this->numeroFacturation = $numeroFacturation;
    }

    /**
    * @param integer $colis_facture
    */
    public function getColisFacture()
    {
        return $this->colisFacture;
    }

    /**
    * @param integer $colis_facture
    */
    public function setColisFacture($colisFacture)
    {
        $this->colisFacture = $colisFacture;
    }

    /**
    * @param integer $montantFacturation
    */
    public function getMontantFacturation()
    {
        return $this->montantFacturation;
    }

    /**
    * @param integer $montantFacturation
    */
    public function setMontantFacturation($montantFacturation)
    {
        $this->montantFacturation = $montantFacturation;
    }
}