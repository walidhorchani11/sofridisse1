<?php

namespace Sogedial\SiteBundle\Service;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Session;
use Sogedial\SiteBundle\Entity\Route;

class RequestListener {

  protected $context;
  protected $em;

    /**
     * RequestListener constructor.
     * @param TokenStorage $context
     * @param EntityManager $manager
     */
  public function __construct(TokenStorage $context, EntityManager $manager) {
    $this->context = $context;
    $this->em = $manager;
  }

  /**
   * kernel.request Event
   *
   * @param GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {

    if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
      return;
    }

    $routeNotSave = array('bazinga_jstranslation_js', 'SogedialSite_searchBar');
    // Si l'utilisateur est connecté, on le track !!
    if ($this->context->getToken() && is_a($this->context->getToken()->getUser(), 'Sogedial\UserBundle\Entity\User')) {
      $request = $event->getRequest();
      $sess = $request->getSession();

      $session = $this->em->getRepository('SogedialSiteBundle:Session')
              ->findOneBy(array('idSession' => $sess->getId()));

      $attr = $request->attributes->all();
      $query = $request->query->all();
      if (!in_array($attr['_route'], $routeNotSave) && isset($attr['_locale'])) {

        // Nouvelle session
        if (is_null($session)) {
          $session = new Session();
          $user = $this->context->getToken()->getUser();
          $server = $request->server->all();
          $userAgent = $this->parse_user_agent($server['HTTP_USER_AGENT']);

          $session->setBrowser($userAgent['browser']);
          $session->setBrowserVersion($userAgent['version']);
          $session->setIp($request->getClientIp());
          $session->setLanguage($attr['_locale']);
          $session->setOs($userAgent['platform']);
          $session->setIdSession($sess->getId());
          $session->setUser($user);

          $this->em->persist($session);
        }

        /*$route = new Route();
        $route->setArguments(json_encode(array_merge($attr['_route_params'], $query)));
        $route->setRoute($attr['_route']);
        $route->setDate(new \DateTime());
        $route->setSession($session);

        $this->em->persist($route);
        $this->em->flush();*/
      }
    }
  }

  private function parse_user_agent($u_agent = null) {
    if (is_null($u_agent)) {
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
      } else {
        throw new \InvalidArgumentException('parse_user_agent requires a user agent');
      }
    }

    $platform = null;
    $browser = null;
    $version = null;

    $empty = array('platform' => $platform, 'browser' => $browser, 'version' => $version);

    if (!$u_agent)
      return $empty;

    if (preg_match('/\((.*?)\)/im', $u_agent, $parent_matches)) {

      preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|iPhone|iPad|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|Nintendo\ (WiiU?|3DS)|Xbox(\ One)?)
                (?:\ [^;]*)?
                (?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);

      $priority = array('Android', 'Xbox One', 'Xbox');
      $result['platform'] = array_unique($result['platform']);
      if (count($result['platform']) > 1) {
        if ($keys = array_intersect($priority, $result['platform'])) {
          $platform = reset($keys);
        } else {
          $platform = $result['platform'][0];
        }
      } elseif (isset($result['platform'][0])) {
        $platform = $result['platform'][0];
      }
    }

    if ($platform == 'linux-gnu') {
      $platform = 'Linux';
    } elseif ($platform == 'CrOS') {
      $platform = 'Chrome OS';
    }

    preg_match_all('%(?P<browser>Camino|Kindle(\ Fire\ Build)?|Firefox|Iceweasel|Safari|MSIE|Trident/.*rv|AppleWebKit|Chrome|IEMobile|Opera|OPR|Silk|Lynx|Midori|Version|Wget|curl|NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
            (?:\)?;?)
            (?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix', $u_agent, $result, PREG_PATTERN_ORDER);


    // If nothing matched, return null (to avoid undefined index errors)
    if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
      return $empty;
    }

    $browser = $result['browser'][0];
    $version = $result['version'][0];

    $find = function ( $search, &$key ) use ( $result ) {
              $xkey = array_search(strtolower($search), array_map('strtolower', $result['browser']));
              if ($xkey !== false) {
                $key = $xkey;

                return true;
              }

              return false;
            };

    $key = 0;
    if ($browser == 'Iceweasel') {
      $browser = 'Firefox';
    } elseif ($find('Playstation Vita', $key)) {
      $platform = 'PlayStation Vita';
      $browser = 'Browser';
    } elseif ($find('Kindle Fire Build', $key) || $find('Silk', $key)) {
      $browser = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
      $platform = 'Kindle Fire';
      if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
        $version = $result['version'][array_search('Version', $result['browser'])];
      }
    } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
      $browser = 'NintendoBrowser';
      $version = $result['version'][$key];
    } elseif ($find('Kindle', $key)) {
      $browser = $result['browser'][$key];
      $platform = 'Kindle';
      $version = $result['version'][$key];
    } elseif ($find('OPR', $key)) {
      $browser = 'Opera Next';
      $version = $result['version'][$key];
    } elseif ($find('Opera', $key)) {
      $browser = 'Opera';
      $find('Version', $key);
      $version = $result['version'][$key];
    } elseif ($find('Midori', $key)) {
      $browser = 'Midori';
      $version = $result['version'][$key];
    } elseif ($find('Chrome', $key)) {
      $browser = 'Chrome';
      $version = $result['version'][$key];
    } elseif ($browser == 'AppleWebKit') {
      if (($platform == 'Android' && !($key = 0))) {
        $browser = 'Android Browser';
      } elseif (strpos($platform, 'BB') === 0) {
        $browser = 'BlackBerry Browser';
        $platform = 'BlackBerry';
      } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
        $browser = 'BlackBerry Browser';
      } elseif ($find('Safari', $key)) {
        $browser = 'Safari';
      }

      $find('Version', $key);

      $version = $result['version'][$key];
    } elseif ($browser == 'MSIE' || strpos($browser, 'Trident') !== false) {
      if ($find('IEMobile', $key)) {
        $browser = 'IEMobile';
      } else {
        $browser = 'MSIE';
        $key = 0;
      }
      $version = $result['version'][$key];
    } elseif ($key = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser']))) {
      $key = reset($key);

      $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
      $browser = 'NetFront';
    }

    return array('platform' => $platform, 'browser' => $browser, 'version' => $version);
  }

}