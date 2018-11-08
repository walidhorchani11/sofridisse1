<?php

namespace Sogedial\IntegrationBundle\Listener;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Service\MultiSiteService;

class SwitchUserListener
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var EntityManager
     */
    private $em;

    private $multisite;

    private $authorizationChecker;

    private $router;

    public function __construct(TokenStorage $tokenStorage, EntityManager $em, MultiSiteService $multisite, AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->multisite = $multisite;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }


    public function onSwitchUser(SwitchUserEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        $user_id = $user->getId();

        $user_meta = $this->em->getRepository('SogedialUserBundle:User')->findOneById($user_id);

        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $user_info = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy( array( "meta" => $user_meta->getMeta()->getCode(), "entreprise" => $user_meta->getEntrepriseCourante()));


            if($this->multisite->hasFeature('tarifs-tarification')){
                $event->getRequest()->getSession()->set('code_tarification', $user_info->getTarification()->getCode());
            } else {
                $event->getRequest()->getSession()->set('code_tarification', null);
            }

            $event->getRequest()->getSession()->set('code_client', $user_info->getCode());
            $event->getRequest()->getSession()->set('code_enseigne', $user_info->getEnseigne()->getCode());
            if($this->multisite->getRegion() === '3'){
                $event->getRequest()->getSession()->set('code_assortiment', '777');
            } else {
                $event->getRequest()->getSession()->set('code_assortiment', $this->multisite->getAssortimentValeur($user_info));
            }
            $event->getRequest()->getSession()->set('entreprise_courante', $user_meta->getEntrepriseCourante());
        }
        else{
            $event->getRequest()->getSession()->remove('code_tarification');
            $event->getRequest()->getSession()->remove('code_client');
            $event->getRequest()->getSession()->remove('code_enseigne');
            $event->getRequest()->getSession()->remove('code_assortiment');
            $event->getRequest()->getSession()->set('entreprise_courante', $user_meta->getEntrepriseCourante());
        }
    }
}