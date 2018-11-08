<?php
namespace Sogedial\SiteBundle\Tests\PHPUnit;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{


    public function setUp()
    {
        parent::setUp();
    }    

    protected function getProtected($class, $method, $args=array())
    {
        $reflection = new \ReflectionClass(get_class($class));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($class,$args);
        return $result;
    }

    protected function pushMockEntityManager($entity)
    {

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())->method('persist')->will($this->returnValue(null));
        $entityManager->expects($this->any())->method('flush')->will($this->returnValue(null));

        $repository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
             ->disableOriginalConstructor()
             ->getMock();
        $repository->expects($this->any())->method('find')->will($this->returnValue($entity));
        $repository->expects($this->any())->method('findBy')->will($this->returnValue($entity));
        $repository->expects($this->any())->method('findOneBy')->will($this->returnValue($entity));
        $entityManager->expects($this->any())->method('getRepository')->will($this->returnValue($repository));

        if (true === method_exists($this->getSvc(), 'setEntityManager')) {
            $this->getSvc()->setEntityManager($entityManager);        
        }

        return $entityManager;
    }

    protected function pushMockSecurityContext($user=null)
    {

        $mockToken = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $mockToken ->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $mockSecurity = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSecurity->expects($this->any())->method('getToken')->will($this->returnValue($mockToken));

        
        if (true === method_exists($this->getSvc(), 'setSecurityContext')) {
            $this->getSvc()->setSecurityContext($mockSecurity);        
        }

        return $mockSecurity;
    }

}
