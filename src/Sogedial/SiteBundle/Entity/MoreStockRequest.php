<?php

namespace Sogedial\SiteBundle\Entity;

use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * MoreStockRequest
 *
 * @ORM\Table(name="more_stock_request")
 * @ORM\Entity(repositoryClass="Sogedial\SiteBundle\Repository\MoreStockRequestRepository")
 */
class MoreStockRequest
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id_more_stock_request", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Promotion", inversedBy="moreStockRequestPromotion")
     * @ORM\JoinColumn(name="code_promotion", referencedColumnName="code_promotion")
     */
    private $promotion;

    /**
     * @ORM\ManyToOne(targetEntity="Sogedial\SiteBundle\Entity\Client", inversedBy="moreStockRequestClients")
     * @ORM\JoinColumn(name="code_client", referencedColumnName="code_client")
     */
    private $client;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity_stock_requested", type="integer")
     */
    private $quantityStockRequested;

    /**
     * Get promotion
     *
     * @return Promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * MoreStockRequest constructor.
     */
    public function __construct()
    {
    }
    
    /**
     * Set promotion
     *
     * @param Promotion $promotion
     * @return Promotion
     */
    public function setPromotion(Promotion $promotion = null)
    {
        $this->promotion = $promotion;
 
        return $this;
    }
 
    /**
    * Get client
    *
    * @return Client
    */
    public function getClient()
    {
        return $this->client;
    }
 
    /**
     * Set client
     *
     * @param Client $client
     * @return Promotion
     */
    public function setClient(Client $client = null)
    {
         $this->client = $client;
 
         return $this;
    }
    
    /**
     * @return integer
     */
    public function getQuantityStockRequested()
    {
        return $this->quantityStockRequested;
    }
 
    /**
     * @param integer $quantityStockRequested
     */
    public function setQuantityStockRequested($quantityStockRequested)
    {
        $this->quantityStockRequested = $quantityStockRequested;
    }
}
