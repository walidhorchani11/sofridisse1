<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\SiteBundle\Entity\OrderContainer as Entity;

class OrderContainer extends AbstractService
{
    /**
     * @var Sogedial\SiteBundle\Service\Container
     */
    protected $svcContainer;

    /**
     * Define the container service
     *
     * @param Sogedial\SiteBundle\Service\Container $value
     * @return void
     */
    public function setSvcContainer($value)
    {
        $this->svcContainer = $value;
    }

    /**
     * Return the container service
     *
     * @return Sogedial\SiteBundle\Service\Container
     */
    public function getSvcContainer()
    {
        return $this->svcContainer;
    }


    /**
     * Create and insert a order container
     *
     * @param \Sogedial\SiteBundle\Entity\Order $order
     * @param string $type
     * @param \Sogedial\SiteBundle\Entity\Container $container
     * @param integer $order
     * @return \Sogedial\SiteBundle\Entity\OrderContainer
     */
    protected function create($order, $type, $container, $ordre)
    {
        $entity = new Entity();
        $entity->setOrder($order);
        $entity->setContainer($container);
        $entity->setOrdre($ordre);
        $entity->setType($type);
        $entity->setCreatedAt(new \DateTime());
        return $this->save($entity);
    }

    /**
     * Return all the orderContainer from an Order and a type
     *
     * @param \Sogedial\SiteBundle\Entity\Order $order
     * @param string $type
     * @return array<\Sogedial\SiteBundle\Entity\OrderContainer>
     */
    public function getByOrder($order, $type=null)
    {
        $where = array('order'=>$order);
        if(null !== $type) {
            $where['type'] = $type;
        }
        return $this->getRepository()->findBy($where);
    }

    /**
     * Save all the containers
     *
     * @param \Sogedial\SiteBundle\Entity\Order $order
     * @param string $type
     * @param array< ordre => container id>
     * @return array<\Sogedial\SiteBundle\Entity\OrderContainer>
     */
    public function saveAll($order, $type, $dataContainers)
    {
        $result = array();
        $oldContainers = $this->getByOrder($order, $type);
        foreach($oldContainers as $orderContainer) {
            $this->delete($orderContainer);
        }

        $result = array();
        foreach($dataContainers as $ordre => $idContainer) {
            $orderContainer = new Entity();
            $orderContainer->setOrdre($ordre);
            $orderContainer->setOrder($order);
            $orderContainer->setType($type);
            $orderContainer->setContainer($this->getSvcContainer()->get($idContainer));
            $result[]= $orderContainer;
        }
        $result = $this->saveArray($result);

        return $result;
    }

    /**
     * Get Order Contianer Info from an order
     *
     * @param \Sogedial\SiteBundle\Entity\Order
     * @return array</StdClass>
     */
    public function getInfos($order)
    {
        $result = array();
        foreach($order->getOrderContainers() as $orderContainer) {
            $id = $orderContainer->getContainer()->getId();
            if (false === array_key_exists($id, $result)) {
                $std            = new \StdClass();
                $std->qty       = 0;
                $std->container = $orderContainer->getContainer();
                $result[$id]    = $std;
            }
            $result[$id]->qty++;
            $result[$id]->total = $result[$id]->qty * $orderContainer->getContainer()->getPrice();
        }
        return $result;
    }
}

