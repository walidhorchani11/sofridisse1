<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\SiteBundle\Entity\ContainerProduct as Entity;

class ContainerProduct extends AbstractService
{

    /**
     * @var \Sogedia\SiteBundle\Service\OrderContainer
     */
     protected $svcOrderContainer;

    /**
     * @param \Sogedia\SiteBundle\Service\OrderContainer
     */
     public function setSvcOrderContainer($value)
     {
         $this->svcOrderContainer = $value;
     }
    
    /**
     * @return \Sogedia\SiteBundle\Service\OrderContainer
     */
     public function getSvcOrderContainer()
     {
         return $this->svcOrderContainer;
     }

    /**
     * Save the distribed container
     *
     * @param string $type
     * @param \Sogedial\SiteBundle\Service\Container\Repartition\Container $container
     * @return void
     */
    public function saveByDistribedContainer($type, $container)
    {
        $orderContainer = $this->getSvcOrderContainer()->get($container->getOrderContainerId());

        $entities = array();
        foreach ($container->getProducts() as $std) {
            $entity = new Entity();
            $entity->setOrderContainer($orderContainer);
            $entity->setOrder($orderContainer->getOrder());
            $entity->setContainer($container->getContainer());
            $entity->setType($type);
            $entity->setProduct($std->product);
            $entity->setQuantity($std->qty);
            $entity->setUnitPrice($std->product->unitPrice);
            $entities[] = $entity;
        }

        $orderContainer->setTotalFillMass($container->getTotalMass());
        $orderContainer->setTotalFillVolume($container->getTotalVolume());
        $orderContainer->setTotalPrice($container->getTotalPrice());
        $orderContainer->setQuantity($container->getNbProducts());

        $this->getSvcOrderContainer()->save($orderContainer);

        return $this->saveArray($entities, true);
    }
}

