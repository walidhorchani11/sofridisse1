<?php
namespace Sogedial\SiteBundle\Tests\Service;

use Sogedial\SiteBundle\Service\CommandeService;
use  Sogedial\SiteBundle\Entity\Commande;
use  Sogedial\SiteBundle\Entity\Entreprise;

class CommandeServiceTest extends \Sogedial\SiteBundle\Tests\PHPUnit\AbstractTest
{
    protected $svc;
    
    public function setUp()
    {
        //$this->svc = null;
        $this->svc = $this->getSvc();
        parent::setUp();        
    }
    

    public function getSvc()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')
        ->disableOriginalConstructor()
        ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
        ->disableOriginalConstructor()
        ->getMock();
        $ms = $this->getMockBuilder('Sogedial\SiteBundle\Service\MultiSiteService')
        ->disableOriginalConstructor()
        ->getMock();
        $ps = $this->getMockBuilder('Sogedial\SiteBundle\Service\ProductService')
        ->disableOriginalConstructor()
        ->getMock();
        $this->svc = new CommandeService($token, $em, $ms, $ps);

        return $this->svc;
    }

    /**
     * @dataProvider deliveryDateProvider
     */
    public function testGetNextDeliveryDate(Commande $commande, \DateTime $date , $expectedDate)
    {
        $this->assertEquals($this->svc->getNextDeliveryDate($commande, $date), $expectedDate);
    }

    public function deliveryDateProvider(){
        //c1 is not a precommande
        $c1 = new Commande();
        //c2 is a precommande
        $c2 = new Commande();
        $e = new Entreprise();
        $e->setTypePreCommande(1);
        $c2->setCodePrecommande($e);
        return [
            [$c1, new \DateTime(), false],
            [$c2, new \DateTime('2017-06-22'), new \DateTime('2017-07-03')],
            [$c2, new \DateTime('2017-06-25'), new \DateTime('2017-07-03')],
            [$c2, new \DateTime('2017-06-26'), new \DateTime('2017-07-10')]
        ];
    }
}

