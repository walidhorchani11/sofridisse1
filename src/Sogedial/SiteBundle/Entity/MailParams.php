<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * MailParams
 *
 * @ORM\Table(name="mail_params")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\MailParamsRepository")
 */
class MailParams
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
     * @ORM\Column(name="type_mail", type="string", length=128, nullable=false)
     */
     private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Entreprise")
     * @ORM\JoinColumn(name="code_entreprise", referencedColumnName="code_entreprise")
     */
     private $entreprise;

    /**
    * @var json_array
    
    * @ORM\Column(type = "json_array") 
    */
    private $mail_cc;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_from", type="string", length=255, nullable=false)
     */
     private $from;
     

    /**
     * @var string
     *
     * @ORM\Column(name="mail_to", type="string", length=255, nullable=true)
     */
     private $to;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_object", type="string", length=255, nullable=true)
     */
     private $object;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_template", type="string", length=255, nullable=false)
     */
     private $template;

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
     * Set type
     *
     * @param string $type
     *
     * @return MailParams
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mailCc
     *
     * @param array $mailCc
     *
     * @return MailParams
     */
    public function setMailCc($mailCc)
    {
        $this->mail_cc = $mailCc;

        return $this;
    }

    /**
     * Get mailCc
     *
     * @return array
     */
    public function getMailCc()
    {
        return $this->mail_cc;
    }

    /**
     * Set from
     *
     * @param string $from
     *
     * @return MailParams
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param string $to
     *
     * @return MailParams
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set object
     *
     * @param string $object
     *
     * @return MailParams
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object
     *
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set template
     *
     * @param string $template
     *
     * @return MailParams
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set entreprise
     *
     * @param \Sogedial\SiteBundle\Entity\Entreprise $entreprise
     *
     * @return MailParams
     */
    public function setEntreprise(\Sogedial\SiteBundle\Entity\Entreprise $entreprise = null)
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * Get entreprise
     *
     * @return \Sogedial\SiteBundle\Entity\Entreprise
     */
    public function getEntreprise()
    {
        return $this->entreprise;
    }
}
