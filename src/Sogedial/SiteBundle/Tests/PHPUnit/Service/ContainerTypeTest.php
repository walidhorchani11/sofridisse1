<?php
namespace Sogedial\SiteBundle\Tests\Service;

use Sogedial\SiteBundle\Service\ContainerType;

class ContainerTypeTest extends \Sogedial\SiteBundle\Tests\PHPUnit\AbstractTest
{
    protected $svc;
    
    public function setUp()
    {
        $this->svc = null;
        parent::setUp();
    }

    public function getSvc()
    {
        if (null === $this->svc) {
            $this->svc = new ContainerType();
        }
        return $this->svc;
    }

    public function testTranscode()
    {
        $this->assertNull(containerType::Transcode('fsfdsf3ambiant'));
        $this->assertEquals(ContainerType::KEY_AMBIANT, containerType::Transcode('3ambiant'));
        $this->assertEquals(ContainerType::KEY_POSITIVE_COLD, containerType::Transcode('1positif'));
        $this->assertEquals(ContainerType::KEY_NEGATIVE_COLD, containerType::Transcode('2negatif'));
    }
}

