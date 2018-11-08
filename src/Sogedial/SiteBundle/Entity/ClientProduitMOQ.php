<?php

namespace Sogedial\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sogedial\SiteBundle\Entity\RegleMOQ;

/**
 * ClientProduitMOQ
 *
 * @ORM\Table(name="client_produit_moq")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\ClientProduitMOQRepository")
 */
class ClientProduitMOQ
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id_regle_moq", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client")
     * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Produit", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="code_produit", referencedColumnName="code_produit")
     */
    private $produit;

    /**
     * @var integer
     *
     * @ORM\Column(name="moq_quantite", type="integer")
     */
    private $quantiteMinimale;

    /**
     * ProduitRegle constructor.
     */
    public function __construct()
    {
    }

    public function getId(){
        return $this->id;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Produit
     */
    public function getProduit()
    {
        return $this->produit;
    }

    /**
     * @param Produit $produit
     */
    public function setProduit(Produit $produit)
    {
        $this->produit = $produit;
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

}