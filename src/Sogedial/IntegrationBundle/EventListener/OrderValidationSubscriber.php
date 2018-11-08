<?php

namespace Sogedial\IntegrationBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Commande;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Sogedial\SiteBundle\Service\CommandeService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Sogedial\SiteBundle\Service\As400CommandeFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class OrderValidationSubscriber implements EventSubscriberInterface
{
    private $messages = [];
    private $arrayPanierId = [];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CommandeService
     */
    private $commandeService;

    /**
     * @var As400CommandeFile
     */
    private $as400Service;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * OrderValidationSubscriber constructor.
     * @param EntityManager $em
     * @param CommandeService $commandeService
     * @param TokenStorage $tokenStorage
     * @param As400CommandeFile $as400Service
     * @param AuthorizationCheckerInterface $authChecker
     */
    public function __construct(EntityManager $em, CommandeService $commandeService, TokenStorage $tokenStorage, As400CommandeFile $as400Service, AuthorizationCheckerInterface $authChecker)
    {
        $this->em = $em;
        $this->commandeService = $commandeService;
        $this->tokenStorage = $tokenStorage;
        $this->as400Service = $as400Service;
        $this->authChecker = $authChecker;
    }

    public function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @return mixed
     */
    public function getArrayPanierId()
    {
        return $this->arrayPanierId;
    }


    /**
     * @param PostResponseEvent $event
     * @return array|void
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $panierIds = $this->getArrayPanierId();

        if(!$panierId = array_shift($panierIds)) {
            return [];
        }
        $panierObject =  $this->em->getRepository('SogedialSiteBundle:Commande')->findOneBy(array('id' => $panierId));
        $weiredPanierIdList = $this->em->getRepository('SogedialSiteBundle:Commande')->getListPanierId();

        if($panierObject instanceof Commande && $panierObject->getUser()->getId() === $panierObject->getValidator()->getId()) {
            $orderOrderObject = $this->em->getRepository('SogedialSiteBundle:OrderOrderStatus')->findOneBy(array('order' => $panierObject->getId()));

            if ( !in_array($panierObject->getId(), $weiredPanierIdList) && null !== $orderOrderObject->getOrderStatus()->getKey() && $orderOrderObject->getOrderStatus()->getKey() === 'STATUS_BASKET_PENDING') {
                $userObject = $panierObject->getValidator();

                $dates = explode(",", $panierObject->isDatesString());
                $comment = str_replace('-', ' ', $panierObject->getCommentaire());
                $validator = $this->getValidatorCommande($userObject);
                $ambientDeliveryDate = $this->commandeService->dateFormatHelper($dates[0]);
                $positiveColdDeliveryDate = $this->commandeService->dateFormatHelper($dates[1]);
                $negativeColdDeliveryDate = $this->commandeService->dateFormatHelper($dates[2]);
                $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrder($panierObject->getId());
                $entrepriseInfos = $this->em->getRepository('SogedialSiteBundle:Commande')->getEntrepriseInfosForRecapByOrder($panierObject->getId());
                $clientInfos = $this->em->getRepository('SogedialSiteBundle:Commande')->getClientInfosForRecapByOrder($panierObject->getId());
                $validatingDate = $this->commandeService->getValidatingDate();
                $now = new \DateTime();

                $productsLignesCommandes = $this->commandeService->setLigneCommandes($orderProducts, $clientInfos, $panierObject, $userObject);
                $this->commandeService->createSubOrders($userObject, $panierObject, $productsLignesCommandes, array_keys($productsLignesCommandes), $comment, $validator, $validatingDate, $now, $now, $ambientDeliveryDate, $positiveColdDeliveryDate, $negativeColdDeliveryDate);

                //Generate AS400 file
                $this->as400Service->handleFile($productsLignesCommandes, $clientInfos, $panierObject->getId(), $entrepriseInfos, $comment, $userObject->getPreCommande() !== NULL);

                //Update orderStatus
                $now = new \DateTime();
                $this->commandeService->updateOrderStatus($panierObject, 'STATUS_BASKET_VALIDATED', $now, $now);

            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::TERMINATE => 'onKernelTerminate'
        );
    }


    public function publish($name, $message)
    {
        if (!isset($this->messages[$name])) {
            $this->messages[$name] = [];
        }

        $this->messages[$name][] = $message;
    }

    public function addPanierId($panierId)
    {
        $this->arrayPanierId[] = $panierId;
    }

    /**
     * @param $currentUser
     * @return mixed
     */
    public function getValidatorCommande($currentUser)
    {
        if ($this->authChecker->isGranted('ROLE_PREVIOUS_ADMIN')) {
            foreach ($this->tokenStorage->getToken()->getRoles() as $role) {
                $roleClassName = get_class($role);
                $switchClassName = 'Symfony\Component\Security\Core\Role\SwitchUserRole';
                if ($roleClassName === $switchClassName) {
                    return $this->em->getRepository('SogedialUserBundle:User')->findOneById($role->getSource()->getUser()->getId());
                }
            }
        }

        return $this->em->getRepository('SogedialUserBundle:User')->findOneById($currentUser->getId());
    }
}