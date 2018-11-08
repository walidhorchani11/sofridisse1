<?php

namespace Sogedial\SiteBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Sogedial\SiteBundle\Service\SimpleMySQLService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;



class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $sql;
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;


    /**
     * AuthenticationSuccessHandler constructor.
     * @param RouterInterface $router
     * @param Session $session
     */
    public function __construct(RouterInterface $router, Session $session, SimpleMySQLService $sql, TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->session = $session;
        $this->sql = $sql;
        $this->tokenStorage = $tokenStorage;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $key = '_security.main.target_path';
        $url = $this->session->get($key);
        $homeUrl = sprintf('%s://%s/', $this->router->getContext()->getScheme(), $this->router->getContext()->getHost());
        $loginCheckUrl = sprintf('%s%s', $homeUrl, $this->router->generate('fos_user_security_check'));

        if ($token->getUser()->hasRole('ROLE_USER')) {  
            $today = new \DateTime(Date("Y-m-d H:i:s"));
            $date_start = $token->getUser()->getDateDebutValidite();       
            $date_end = $token->getUser()->getDateFinValidite();  
            
            if($date_end != null && $today > $date_end){
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $token->getUser()->setEnabled(false);

                $this->sql->query("UPDATE fos_user u SET enabled = false WHERE u.id_utilisateur=".$token->getUser()->getId());
                throw new BadCredentialsException("Compte non valide");
            }

                        
            if($date_start != null && $today < $date_start){
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $token->getUser()->setEnabled(false);
                throw new BadCredentialsException("Compte non valide");
            }
        }

        if (($url == $homeUrl) || ($url == $loginCheckUrl) || ($url === null) || ($url =='')) {

            if ($token->getUser()->hasRole('ROLE_ADMIN')) {
                $url = $this->router->generate('sogedial_integration_admin_dashbord');
            } elseif ($token->getUser()->hasRole('ROLE_USER')) {
                $url = $this->router->generate('sogedial_integration_societe_landing');
            }
        }

        if ($token->getUser()->hasRole('ROLE_USER')) {
            $user = $token->getUser();
            $user_id = $user->getId();

            $user_meta = $this->sql->query("SELECT u.meta from fos_user u WHERE u.id_utilisateur=".$user_id);

            $query = "SELECT c.code_meta_client, c.code_client, c.code_enseigne, c.code_tarification, c.code_assortiment, c.code_entreprise, u.entreprise_courante, u.pre_commande 
                from fos_user u
                INNER JOIN  client c ON c.code_meta_client='".$user_meta[0]['meta']."' AND c.code_entreprise = ". $user->getEntrepriseCourante() ."
                WHERE u.entreprise_courante = ". $user->getEntrepriseCourante() ."";
            $users_info = $this->sql->query($query);

            foreach ($users_info as $key => $user_info) {
                if($user_info['entreprise_courante'] == $user->getEntrepriseCourante()){
                    if($user_info["pre_commande"] !== NULL){
                        $url = $this->router->generate('sogedial_integration_societe_landing');
                    }
                    $this->session->set('code_client', $user_info['code_client']);
                    $this->session->set('code_enseigne', $user_info['code_enseigne']);
                    $this->session->set('code_tarification', $user_info['code_tarification']);
                    $this->session->set('code_assortiment', substr($user_info['code_assortiment'],4,3));
                    $this->session->set('entreprise_courante', $user_info['entreprise_courante']);
                    break;
                }
            }
        }

        if ($token->getUser()->hasRole('ROLE_ADMIN')) {
            $user = $token->getUser();
            $user_entreprise_courante = $user->getEntrepriseCourante();
            $this->session->set('entreprise_courante', $user_entreprise_courante);
        }

        
        $this->session->remove($key);
        return new RedirectResponse($url);
    }

}
