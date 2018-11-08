<?php
namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Zone
 *
 * @ORM\Table(name="zone")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ZoneRepository")
 */
class Zone
{
    /**
     * @var integer
     *
     * @ORM\Column(name="code_zone", type="integer", length=1, nullable=false, unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_zone", type="string", length=255)
     */
    private $nom;

    /**
     * @var boolean
     *
     * @ORM\Column(name="lundi", type="boolean", length=1)
     */
    private $lundi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mardi", type="boolean", length=1)
     */
    private $mardi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mercredi", type="boolean", length=1)
     */
    private $mercredi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="jeudi", type="boolean", length=1)
     */
    private $jeudi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="vendredi", type="boolean", length=1)
     */
    private $vendredi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="samedi", type="boolean", length=1)
     */
    private $samedi;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dimanche", type="boolean", length=1)
     */
    private $dimanche;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature", type="string", length=255)
     */
    private $temperature;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
    private $entreprise;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
    * @return integer
    */
    public function getCode()
    {
        return $this->code;
    }

    /**
    * @return integer
    */
    public function getLundi()
    {
        return $this->lundi;
    }

    /**
    * @return integer
    */
    public function getMardi()
    {
        return $this->mardi;
    }

    /**
    * @return integer
    */
    public function getMercredi()
    {
        return $this->mercredi;
    }

    /**
    * @return integer
    */
    public function getJeudi()
    {
        return $this->jeudi;
    }
    /**
    * @return integer
    */
    public function getVendredi()
    {
        return $this->vendredi;
    }

    /**
    * @return integer
    */
    public function getSamedi()
    {
        return $this->samedi;
    }

    /**
    * @return integer
    */
    public function getDimanche()
    {
        return $this->dimanche;
    }

    /**
    * @return array
    */
    public function getJoursOuverture()
    {
        $days = [];
        if($this->getDimanche()){
            array_push($days, 0);
        }
        if($this->getLundi()){
            array_push($days, 1);
        }
        if($this->getMardi()){
            array_push($days, 2);
        }
        if($this->getMercredi()){
            array_push($days, 3);
        }
        if($this->getJeudi()){
            array_push($days, 4);
        }
        if($this->getVendredi()){
            array_push($days, 5);
        }
        if($this->getSamedi()){
            array_push($days, 6);
        }
        return $days;
    }

    /**
    * @return \DateTime
    */
    public function getDeliveryNextDate(){
        $date = new \DateTime();
        //on prend le jour actuel de la semaine 
        $d = intval($date->format('w'));
        $opening = $this->getJoursOuverture();
        $i = 0;

        //jamais ouvert
        if(count($opening) === 0){
            return false;
        }

        for(; $i < 7; $i++){
            $r = false;
            switch($d){
                case 0: $r = $this->getDimanche(); break;
                case 1: $r = $this->getLundi(); break;
                case 2: $r = $this->getMardi(); break;
                case 3: $r = $this->getMercredi(); break;
                case 4: $r = $this->getJeudi(); break;
                case 5: $r = $this->getVendredi(); break;
                case 6: $r = $this->getSamedi(); break;
            }
            if($r === true){
                break;
            }
            $d = ($d + 1) % 7;
        }

        $date->add(new \DateInterval("P". $i ."D"));
        return $date;
    }

    /**
    * @return integer
    */
    public function setLundi($enable)
    {
        return $this->lundi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setMardi($enable)
    {
        return $this->mardi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setMercredi($enable)
    {
        return $this->mercredi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setJeudi($enable)
    {
        return $this->jeudi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setVendredi($enable)
    {
        return $this->vendredi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setSamedi($enable)
    {
        return $this->samedi = (!$enable) ? 0 : 1;
    }

    /**
    * @return integer
    */
    public function setDimanche($enable)
    {
        return $this->dimanche = (!$enable) ? 0 : 1;
    }

    /**
    * @return string
    *
    */
    public function getNom()
    {
        return $this->nom;
    }

    /**
    * @param string
    *
    */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    public function __toString()
    {
        $str = strval($this->getNom());

        $strDays = "";
        if($this->getLundi()){
            $strDays .= "Lun";
        }
        if($this->getMardi()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }
            $strDays .= "Mar";
        }
        if($this->getMercredi()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }
            $strDays .= "Mer";
        }
        if($this->getJeudi()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }
            $strDays .= "Jeu";
        }
        if($this->getVendredi()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }
            $strDays .= "Ven";
        }
        if($this->getSamedi()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }   
            $strDays .= "Sam";
        }
        if($this->getDimanche()){
            if(strlen($strDays) > 0){
                $strDays .= ", ";
            }
            $strDays .= "Dim";
        }
        return $str . " (" . $strDays . " )" ;
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
     * @return Promotion
     */
    public function setEntreprise(Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * @param string $temperature
     */
    public function setTemperature($temperature)
    {
        $this->temperature = $temperature;
    }

}