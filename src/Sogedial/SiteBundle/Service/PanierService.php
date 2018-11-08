<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\SiteBundle\Entity\Promotion;
use Sogedial\UserBundle\Entity\User;
use Sogedial\SiteBundle\Entity\Produit;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\LigneCommande;
use Sogedial\SiteBundle\Entity\OrderOrderStatus;
use Doctrine\ORM\EntityManager;

class PanierService extends AbstractService
{
    /**
     * @var EntityManager
     */
    private $em;
    private $ms;
    private $ps;
    private $pr;

    public function __construct(EntityManager $em, MultiSiteService $multisite, ProductService $ps, PromotionService $pr)
    {
        $this->em = $em;
        $this->ms = $multisite;
        $this->ps = $ps;
        $this->pr = $pr;
    }

    public function initOrder(User $user)
    {
        $order = $this->createOrder($user);
        $orderOrderStatus = $this->initOrderStatusCommande($order);
        $this->em->persist($order);
        $this->em->persist($orderOrderStatus);
        $this->em->flush();

        return $order;
    }

    public function getCurrentOrder(User $user){
        return $this->em->getRepository('SogedialSiteBundle:Commande')
                    ->getCurrentOrderByUser(
                        $user->getId(),
                        $user->getEntrepriseCourante()
                    );
    }

    public function initOrderStatusCommande(Commande $commande)
    {
        $orderOrderStatus = new OrderOrderStatus();
        $orderOrderStatus->setOrder($commande);
        $orderOrderStatus->setOrderStatus(
            $this->em->getRepository('SogedialSiteBundle:OrderStatus')->findOneByKey('STATUS_CURRENT')
        );
        $orderOrderStatus->setCreatedAt(new \DateTime());
        $orderOrderStatus->setUpdatedAt(new \DateTime());

        return $orderOrderStatus;
    }

    public function createOrder(User $currentUser)
    {
        $entreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise')
        ->findOneBy(array('code' => $this->ms->getSociete()));
        $entrepriseCourante = $this->em->getRepository('SogedialSiteBundle:Entreprise')
        ->findOneBy(array('code' => $currentUser->getEntrepriseCourante()));
        $currentOrder = new Commande();
        if($currentUser->getPreCommande() !== NULL && $entrepriseCourante->getTypePreCommande() !== NULL){
            $currentOrder->setCodePrecommande($entrepriseCourante);
        }
        $currentOrder->setApplicationOrigine("A7");
        $currentOrder->setUser($currentUser);
        $currentOrder->setEntreprise($entrepriseCourante);

        return $currentOrder;
    }

    public function editOrder($currentOrder, $currentUser, $entreprise, $product, $quantity)
    {
        $currentOrderProduct = $this->em->getRepository('SogedialSiteBundle:LigneCommande')
                                            ->getCurrentOrderProductByUserAndProduct($currentUser, $entreprise, $product);

        if ($currentOrderProduct === null) {
            $hasMOQ = $this->em->getRepository('SogedialSiteBundle:ProduitRegle')
                                    ->findOneByCode($product->getCode());

            if($hasMOQ !== NULL){
                $hasMOQ = false;
            }

            $entityClient = $this->em->getRepository('SogedialSiteBundle:Client')
                                        ->findOneBy(array("meta" => $currentUser->getMeta()->getCode(), "entreprise" => $currentUser->getEntrepriseCourante()));
            $promotion =  $this->em->getRepository('SogedialSiteBundle:Promotion')
                                        ->getPromotionByProduct($product, $entityClient, $entityClient->getEnseigne());

            $currentOrderProduct = new LigneCommande();
            $currentOrderProduct->setCommande($currentOrder)
                ->setProduit($product)
                ->setQuantite($quantity);
                if($promotion instanceof Promotion) {
                    $currentOrderProduct->setPromotion($promotion);
                } else {
                    $currentOrderProduct->setPromotion(NULL);
                }


            if($currentUser->getPreCommande() !== NULL) {
                $currentOrderProduct->setMOQ($hasMOQ);
            }
            if($product->getTemperature() === 'SEC') {
                $currentOrderProduct->setTemperatureProduit('ambient');
            }
            if($product->getTemperature() === 'FRAIS') {
                $currentOrderProduct->setTemperatureProduit('positiveCold');
            }
            if($product->getTemperature() === 'SURGELE') {
                $currentOrderProduct->setTemperatureProduit('negativeCold');
            }

        }

        return $currentOrderProduct;
    }

    public function editProductToCurrentOrder(
        Commande $currentOrder,
        Client $client,
        $codeTarification,
        $valeurAssortiment,
        User $user,
        Produit $product,
        $quantity,
        $unitedPromos ){

        $currentOrderProduct = null;
        $entreprise = $user->getEntrepriseCourante();
        $hasTarifTarificationFeature = $this->ms->hasFeature('tarifs-tarification');

        /*
        if ($currentOrder === null) {
            $currentOrderProduct =$this->createOrder($user);
            if($currentOrderProduct){
                $this->initOrderStatusCommande($currentOrderProduct);
                $this->editOrder($currentOrder, $user, $entreprise, $product, $quantity);
            }
        } else {
            $currentOrderProduct = $this->editOrder($currentOrder, $user, $entreprise, $product, $quantity);
        }
        */
        $currentOrderProduct = $this->editOrder($currentOrder, $user, $entreprise, $product, $quantity);
        $this->em->persist($currentOrderProduct);
        $this->em->flush();

        $pcb = $product->getPcb();
        $priceArray = $this->ps->getActualProductPriceAndStock($product,
            $unitedPromos)['priceArray'];
        $lineItemTotal = $this->ps->getLineItemTotal($quantity, $pcb, $priceArray);
        $lineItemUnitPrice = $this->ps->getDegressivePrice($quantity, $priceArray);

        $currentOrderProduct->setQuantite($quantity);
        $currentOrderProduct->setPrixUnitaire(number_format($lineItemUnitPrice, 2, '.', ''));
        $currentOrderProduct->setMontantTotal(number_format($lineItemTotal, 2, '.', ''));

        $this->em->getRepository(
            'SogedialSiteBundle:Commande'
        )->getCurrentOrderByUser($user, $user->getEntrepriseCourante())->setUpdatedAt(new \DateTime());

        $this->em->flush();
    }

    public function handleBracketChanges(User $user, $references){
        $produitRepository = $this->em->getRepository('SogedialSiteBundle:Produit');

        $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array(
            "meta" => $user->getMeta()->getCode(),
            "entreprise" => $user->getEntrepriseCourante())
        );
        if($this->ms->getRegion() === '3'){
            $valeurAssortiment = '777';
        } else {
            $valeurAssortiment = $this->ms->getAssortimentValeur($client);
        }
        $tarification = $client->getTarification();
        $codeTarification = ($tarification ? ($tarification->getCode()) : null);
        $unitedPromos = $this->pr->getUnitedPromos();
        $order = $this->getCurrentOrder($user);

        if(!$order){
            $order = $this->initOrder($user);
        }

        foreach($references as $refProduct => $quantity){
            $product = $produitRepository->find($refProduct);
            $this->editProductToCurrentOrder($order,$client, $codeTarification, $valeurAssortiment, $user, $product, intval($quantity), $unitedPromos);
        }
    }

    /**
    * Get the next monday
    * @param User user
    * @return mixed
    */
    public function getDateLivraisonEstimee(User $user)
    {
        // l'estimation de livraison n'a lieu que pour une précommande et ne doit pas être utilisé en dehors de ce contexte.
        if($user->getPreCommande() === null){
            return false;
        }

        $estimatedDeliveryDate = new \DateTime('now');
        $indexSummerDay = intval($estimatedDeliveryDate->format("N"));
        $estimatedDeliveryDate->add(new \Dateinterval('P' . (8 - $indexSummerDay) . 'D'));

        return $estimatedDeliveryDate;
    }
}
