<?php

namespace Sogedial\SiteBundle\Twig;

use Sogedial\SiteBundle\Service\MultiSiteService;

class MasterEnterpriseExtension extends \Twig_Extension
{

    private $ms;

    public function __construct(MultiSiteService $ms)
    {
        $this->ms = $ms;
    }


    public function getGlobals()
    {
        return array(
            "MasterEnterprise" => $this->ms->getMasterEnterpriseTwig(),
        );
    }

    public function getName()
    {
        return 'MasterEnterprise_extention';
    }
}