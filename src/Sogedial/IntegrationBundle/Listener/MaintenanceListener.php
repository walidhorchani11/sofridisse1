<?php

namespace Sogedial\IntegrationBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceListener
{

    protected $kernel;

    /**
     * MaintenanceListener constructor.
     * @param $kernel
     */
    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $maintenance = $this->kernel->getContainer()->hasParameter('maintenance') ? $this->kernel->getContainer()->getParameter('maintenance') : false;

        $debug = in_array($this->kernel->getContainer()->get('kernel')->getEnvironment(), array('test', 'dev'));

        if ($maintenance && !$debug) {
            $engine = $this->kernel->getContainer()->get('templating');

            $content = $engine->render('::maintenance.html.twig');
            $event->setResponse(new Response($content, 503));
            $event->stopPropagation();
        }

    }

}