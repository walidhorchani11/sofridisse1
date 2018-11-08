<?php

namespace Sogedial\SiteBundle\Entity;

use ClassesWithParents\D;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sogedial\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints\DateTime;


/**
 * MetaClient
 *
 * @ORM\Table(name="metaClient")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\MetaClientRepository")
 */
class MetaClient
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_meta", type="string", length=64, nullable=false, unique=true)
     * @ORM\Id
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="libelle", type="string", length=255, nullable=true)
     */
    private $libelle;

    /**
     * @var Datetime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\SiteBundle\Entity\Client", mappedBy="meta", cascade={"persist"})
     */
    private $clients;

    /**
     * @ORM\OneToMany(targetEntity="Sogedial\UserBundle\Entity\User", mappedBy="meta")
     */
    private $user;

    /**
     * MetaClient constructor.
     */
    public function __construct()
    {
        $this->clients = new ArrayCollection();
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
     * @return mixed
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param mixed $libelle
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $libelle
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Add photos
     *
     * @param Client $clients
     * @return Client
     */
    public function addClient(Client $clients)
    {
        $this->clients[] = $clients;
        $clients->setMetaClient($this);

        return $this;
    }

    /**
     * Remove clients
     *
     * @param Client $clients
     */
    public function removeClient(Client $clients)
    {
        $this->clients->removeElement($clients);
    }

    /**
     * Get clients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function __toString()
    {
        return $this->getLibelle();
    }

}