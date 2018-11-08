<?php

namespace Sogedial\IntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DidacticielController extends Controller
{
	public function finishFirstVisitAction()
    {
    	$em = $this->getDoctrine()->getManager();

    	$currentUser = $this->getUser();
        $currentUser->setPremiereVisite(0);

        $em->persist($currentUser);
        $em->flush();

        return new JsonResponse(
            array(
                'response' => 'Client will no more receive didacticiel.'
            )
        );
    }
}