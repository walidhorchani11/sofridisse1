<?php

namespace Sogedial\IntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Sogedial\IntegrationBundle\EventListener\Queues;

class SettingsController extends Controller
{
    /**
     * @return Response
     */
    public function readPdfAction()
    {
        $em = $this->getDoctrine()->getManager();
        $currentUser = $this->getUser();
        $entrepriseObj = $em->getRepository('SogedialSiteBundle:Entreprise')->findOneByCode($currentUser->getEntrepriseCourante());

        $filename = $this->get('sogedial.multisite')->getTrigram() . '_CCV_2018.pdf';             // par exemple, SOF_CCV_2017.pdf
        $pdfFile = sprintf('%s/%s', $this->container->getParameter('cgv_upload_dir'), $filename);

        if (file_exists($pdfFile) && mime_content_type($pdfFile) == 'application/pdf') {       // sur Windows, ajoutez l'extension extension=php_fileinfo.dll dans php.ini
            $response = new BinaryFileResponse($pdfFile);

            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE, $filename
            );

            return $response;
        }

        return $this->redirect($this->generateUrl('sogedial_integration_dashbord', array('societe' => $entrepriseObj->getNomEnvironnement())));
    }

    /**
     * @return JsonResponse
     */
    public function signeConditionsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $userObj = $this->getUser();

        if (is_object($userObj)) {
            $userObj->setCgvCpvSignedAt(new \DateTime('now'));
            $em->persist($userObj);
            $em->flush();

            return new JsonResponse(
                array(
                    'message' => 'true'
                )
            );
        }

        return new JsonResponse(
            array(
                'message' => 'false'
            )
        );
    }

    /**
     * @return JsonResponse
     */
    public function conditionToTrueReadySignedAction()
    {
        $em = $this->getDoctrine()->getManager();

        $userObj = $this->getUser();

        if (is_object($userObj)) {
            $userObj->setAlreadySigned(true);
            $em->persist($userObj);
            $em->flush();
        }

        return new JsonResponse(
            array(
                'message' => 'true'
            )
        );
    }

    /**
     * @return JsonResponse
     */
    public function conditionToFalseReadySignedAction()
    {
        $em = $this->getDoctrine()->getManager();

        $userObj = $this->getUser();

        if (is_object($userObj)) {
            $userObj->setAlreadySigned(false);
            $em->persist($userObj);
            $em->flush();
        }

        return new JsonResponse(
            array(
                'message' => 'false'
            )
        );
    }
}