<?php
namespace Sogedial\SiteBundle\Tests\Service;

use Sogedial\SiteBundle\Service\CommandeService;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Colis;
use Sogedial\SiteBundle\Entity\RegleMOQ;
use Sogedial\SiteBundle\Entity\LigneCommande;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MoqServiceTest extends KernelTestCase// \Sogedial\SiteBundle\Tests\PHPUnit\AbstractTest
{
    protected $svc;
    private $em;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();

        //$this->svc = null;
        $this->svc = $this->getSvc();
        parent::setUp();        
    }

    public function getSvc()
    {
        $token = $this->getMockBuilder(
                'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $ms = $this->getMockBuilder(
                'Sogedial\SiteBundle\Service\MultiSiteService'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $ps = $this->getMockBuilder(
                'Sogedial\SiteBundle\Service\ProductService'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->svc = new CommandeService($token, $this->em, $ms, $ps);

        return $this->svc;
    }

    /**
     * @dataProvider moqRulesKiloBelowProvider
     */
    public function testMoqKiloBelowLigneCommand($quantiteMinimale, $unite, $group, $mix)
    {
        $regleMOQ = new RegleMOQ();
        $regleMOQ->setQuantiteMinimale($quantiteMinimale);
        $regleMOQ->setUnite($unite);
        $regleMOQ->setGroup($group);
        $regleMOQ->setMix($mix);

        $colis = new Colis();
        $colis->setPoidsBrutUVC("5.80");

        $ligneCommandeRepositoryMock = $this
            ->getMockBuilder(
                '\Sogedial\SiteBundle\Repository\LigneCommandeRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('getMOQRuleFromProductOfLigneCommande')
            ->will($this->returnValue($regleMOQ));
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('findOneBy') //search colis
            ->will($this->returnValue($colis));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($ligneCommandeRepositoryMock));

        $this->container->set('doctrine.orm.entity_manager',$this->em);

        $produit = new Produit();
        $produit->setCode("5");

        $ligneCommande = new LigneCommande();
        $ligneCommande->setProduit($produit);
        $ligneCommande->setQuantite(10);

        $this->assertEquals($this->svc->handleMOQLigneCommand($ligneCommande), true);
    }

    public function moqRulesKiloBelowProvider(){
        return [
            [null, "kg", false, false], //correct or not ? 
            [0, "kg", false, true],
            [-1, "kg", true, false],
            [1, "kg", true, true],
            [5, "kg", true, true],
            [5.78, "kg", true, true],
        ];
    }

    /**
    * @dataProvider moqRulesKiloAboveProvider
    */
    public function testMoqKiloAboveLigneCommand($quantiteMinimale, $unite, $group, $mix)
    {
        $regleMOQ = new RegleMOQ();
        $regleMOQ->setQuantiteMinimale($quantiteMinimale);
        $regleMOQ->setUnite($unite);
        $regleMOQ->setGroup($group);
        $regleMOQ->setMix($mix);

        $colis = new Colis();
        $colis->setPoidsBrutUVC("5.80");

        $ligneCommandeRepositoryMock = $this
            ->getMockBuilder(
                '\Sogedial\SiteBundle\Repository\LigneCommandeRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('getMOQRuleFromProductOfLigneCommande')
            ->will($this->returnValue($regleMOQ));
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('findOneBy') //search colis
            ->will($this->returnValue($colis));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($ligneCommandeRepositoryMock));

        $this->container->set('doctrine.orm.entity_manager',$this->em);

        $produit = new Produit();
        $produit->setCode("5");

        $ligneCommande = new LigneCommande();
        $ligneCommande->setProduit($produit);
        $ligneCommande->setQuantite(1);

        $this->assertEquals($this->svc->handleMOQLigneCommand($ligneCommande), false);
    }

    public function moqRulesKiloAboveProvider(){
        return [
            [1000, "kg", true, true],
            [15, "kg", true, true],
            [8.91, "kg", true, true]
        ];
    }

    /**
     * @dataProvider moqRulesUCBelowProvider
     */
    public function testMoqUCBelowLigneCommand($quantiteMinimale, $unite, $group, $mix)
    {
        $regleMOQ = new RegleMOQ();
        $regleMOQ->setQuantiteMinimale($quantiteMinimale);
        $regleMOQ->setUnite($unite);
        $regleMOQ->setGroup($group);
        $regleMOQ->setMix($mix);

        $colis = new Colis();
        $colis->setPoidsBrutUVC("5.80");

        $ligneCommandeRepositoryMock = $this
            ->getMockBuilder(
                '\Sogedial\SiteBundle\Repository\LigneCommandeRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('getMOQRuleFromProductOfLigneCommande')
            ->will($this->returnValue($regleMOQ));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($ligneCommandeRepositoryMock));

        $this->container->set('doctrine.orm.entity_manager',$this->em);

        $produit = new Produit();
        $produit->setCode("5");

        $ligneCommande = new LigneCommande();
        $ligneCommande->setProduit($produit);
        $ligneCommande->setQuantite(10);

        $this->assertEquals($this->svc->handleMOQLigneCommand($ligneCommande), true);
    }

    public function moqRulesUCBelowProvider(){
        return [
            [null, "uc", false, false], //correct or not ? 
            [0, "uc", false, true],
            [-1, "uc", true, false],
            [1, "uc", true, true],
            [5, "uc", true, true],
            [5.78, "uc", true, true],
        ];
    }

    /**
    * @dataProvider moqRulesUCAboveProvider
    */
    public function testMoqUCAboveLigneCommand($quantiteMinimale, $unite, $group, $mix)
    {
        $regleMOQ = new RegleMOQ();
        $regleMOQ->setQuantiteMinimale($quantiteMinimale);
        $regleMOQ->setUnite($unite);
        $regleMOQ->setGroup($group);
        $regleMOQ->setMix($mix);

        $colis = new Colis();
        $colis->setPoidsBrutUVC("5.80");

        $ligneCommandeRepositoryMock = $this
            ->getMockBuilder(
                '\Sogedial\SiteBundle\Repository\LigneCommandeRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();
        $ligneCommandeRepositoryMock->expects($this->once())
            ->method('getMOQRuleFromProductOfLigneCommande')
            ->will($this->returnValue($regleMOQ));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($ligneCommandeRepositoryMock));

        $this->container->set('doctrine.orm.entity_manager',$this->em);

        $produit = new Produit();
        $produit->setCode("5");

        $ligneCommande = new LigneCommande();
        $ligneCommande->setProduit($produit);
        $ligneCommande->setQuantite(1);

        $this->assertEquals($this->svc->handleMOQLigneCommand($ligneCommande), false);
    }

    public function moqRulesUCAboveProvider(){
        return [
            [1000, "kg", true, true],
            [15, "kg", true, true],
            [8.91, "kg", true, true]
        ];
    }
}
