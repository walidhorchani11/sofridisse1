<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class AbstractEntity
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
     * @ORM\Column(name="date_create", type="datetime")
     */
    private $date_create;

    /**
     * @var string
     *
     * @ORM\Column(name="date_update", type="datetime", nullable=true)
     */
    private $date_update;


    public function __construct()
    {
        $this->date_create = new \DateTime();
    }

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
     * Set date_create
     *
     * @param \DateTime $dateCreate
     * @return AbstractEntity
     */
    public function setDateCreate($dateCreate)
    {
        $this->date_create = $dateCreate;

        return $this;
    }

    /**
     * Get date_create
     *
     * @return \DateTime 
     */
    public function getDateCreate()
    {
        return $this->date_create;
    }

    /**
     * Set date_update
     *
     * @ORM\PreUpdate
     * @param \DateTime $dateUpdate
     * @return AbstractEntity
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->date_update = new \DateTime();

        return $this;
    }

    /**
     * Get date_update
     *
     * @return \DateTime 
     */
    public function getDateUpdate()
    {
        return $this->date_update;
    }
}
