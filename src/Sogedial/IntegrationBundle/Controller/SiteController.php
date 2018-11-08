<?php

namespace Sogedial\IntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SiteController extends Controller
{
    public function indexAction()
    {
        return $this->redirect(
            $this->generateUrl('sogedial_integration_societe_landing', array('_locale' => $this->getUser()->getLocale()))
        );
    }
}