<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class UserInfo
{
    /**
     * @var Session
    */
    protected $session;
    protected $tokenStorage;

    public $isAdmin;

    public $user_id;                    // dans fos_user
    public $code_client;
    public $code_enseigne;
    public $code_tarification;
    public $code_assortiment;           // en fait, il s'agit de la valeur d'assortiment ici (à 3 chiffres). Mais je lasse le nom car un jour assortiment sera "un ensemble des produit" et pas "un produit dans un ensemble de produits", comme aujourd'hui
    public $entreprise_courante;

    private function setUser(){
        $this->code_client = $this->session->get('code_client');
        $this->code_enseigne = $this->session->get('code_enseigne');
        $this->code_tarification = $this->session->get('code_tarification');
        $this->code_assortiment = $this->session->get('code_assortiment');
        $this->entreprise_courante = $this->session->get('entreprise_courante');
    }

    public function switchClient($codeClient, $codeEnseigne, $codeTarification, $codeAssortiment, $societe)
    {
        $this->session->set('code_client', $codeClient);
        $this->session->set('code_enseigne', $codeEnseigne);
        $this->session->set('code_tarification', $codeTarification);
        $this->session->set('code_assortiment', $codeAssortiment);
        $this->session->set('entreprise_courante', $societe);
        $this->setUser();
    }

    public function initSessionUser()
    {
        $token = $this->tokenStorage->getToken();
        $this->pre_commande = NULL;
        if ($token) {
            $user = $token->getUser();
            $this->pre_commande = $token->getUser()->getPreCommande();

            $this->isAdmin = false;
            if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_ADMIN'))
            {
                 $this->isAdmin = true;
            }
            $this->setUser();
        }

    }

    public function __construct(TokenStorage $tokenStorage, Session $session)
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->initSessionUser();
    }

    // ce commentaire est probablement obsolète depuis l'existence de mécanisme de switchUser

    // TODO méthode pour "sélectionner" un utilisateur (pour un admin)
    // + des moyens de distinguer les "vraies" infos et celles de "assumedrole"

}