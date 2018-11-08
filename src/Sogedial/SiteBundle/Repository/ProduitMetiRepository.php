<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Sogedial\SiteBundle\Entity\ProduitMeti;

/**
 * ProduitMetiRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProduitMetiRepository extends EntityRepository
{
    public function getUnreferencedProductByCommandId($orderId) {
        $codeClientOrigine = $this->_em->getRepository('SogedialSiteBundle:Commande')->getCodeClientOriginal($orderId);

        if($codeClientOrigine == null){
            return array();
        }
        $commande = $this->_em->getRepository('SogedialSiteBundle:Commande')->findOneById($orderId);
        $produitsPrecoToRef = array();
        setlocale(LC_TIME, 'fra_fra');
        
        $lignesCommandes = $this->_em->getRepository('SogedialSiteBundle:LigneCommande')->getLigneByOrderId($orderId);
        foreach ($lignesCommandes as $key => $lignesCommande) {
            if( $this->_em->getRepository('SogedialSiteBundle:Produit')->findOneBy(array('code' => $lignesCommande->getProduit()->getCode()))->getPreCommande() ) {
                $produitMeti = $this->_em->getRepository('SogedialSiteBundle:ProduitMeti')->findOneBy(array( 'client' => $codeClientOrigine, 'produit' => $lignesCommande->getProduit()->getCode()));
                if($produitMeti == null){
                    array_push($produitsPrecoToRef, array(
                        "entreprise_commande" => $commande->getEntreprise()->getCode(),
                        "client_commande" => $codeClientOrigine,
                        "client_as400" => explode( '-',$codeClientOrigine)[1],
                        "produit_commande" => $lignesCommande->getProduit()->getCode(), 
                        "produit_as400" => explode( '-',$lignesCommande->getProduit()->getCode())[1], 
                        "denomination" => $lignesCommande->getProduit()->getDenominationProduitBase(),
                        "num_cmd" => $commande->getNumero(), 
                        "date_validation" => strftime ('%d/%m/%y', $commande->getValidatingDate()->getTimestamp()), 
                        "date_delivery" => strftime ('%d/%m/%y', $commande->getDeliveryDate()->getTimestamp())));
                }
            }
        }
        return $produitsPrecoToRef;
    }

}
