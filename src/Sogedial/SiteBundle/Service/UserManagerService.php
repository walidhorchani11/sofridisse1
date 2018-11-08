<?php

namespace Sogedial\SiteBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UserManagerService {

  private $container;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  public function getParametersBlockOrphans() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $queryCount = $repository->createQueryBuilder('u');
    $elementsLength = $queryCount->select('COUNT(u.id)')
        ->where($queryCount->expr()->notIn('u.statut', array('lock', 'deny', 'pending', 'validate')))
        ->getQuery()
        ->getSingleScalarResult();

    return array(
      'elements_length' => $elementsLength,
    );
  }

  public function getParametersBlockDeny() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $elementsLength = $repository->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'deny')
        ->getQuery()
        ->getSingleScalarResult();

    return array(
      'elements_length' => $elementsLength,
    );
  }

  public function getParametersBlockLock() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $elementsLength = $repository->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'lock')
        ->getQuery()
        ->getSingleScalarResult();

    return array(
      'elements_length' => $elementsLength,
    );
  }

  public function getParametersBlockPending() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $elementsLength = $repository->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'pending')
        ->getQuery()
        ->getSingleScalarResult();

    return array(
      'elements_length' => $elementsLength,
    );
  }

  public function getParametersBlockValidate() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $elementsLength = $repository->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'validate')
        ->getQuery()
        ->getSingleScalarResult();

    return array(
      'elements_length' => $elementsLength,
    );
  }

  public function getParametersBlockByState($state) {
    $method_name = 'getParametersBlock' . ucfirst($state);
    $parameters = array('elements_length' => 0);
    
    if (method_exists($this, $method_name)) {
      $parameters = $this->$method_name();
    }

    $parameters['state'] = $state;
      
    return $parameters;
  }
  
  public function getElementByStateOrphans() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    $query = $repository->createQueryBuilder('u');
    return $query->where($query->expr()->notIn('u.statut', array('lock', 'deny', 'pending', 'validate')))
        ->getQuery()
        ->getArrayResult();
  }

  public function getElementByStatePending() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    return $repository->createQueryBuilder('u')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'pending')
        ->getQuery()
        ->getArrayResult();
  }
  
  public function getElementByStateValidate() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    return $repository->createQueryBuilder('u')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'validate')
        ->getQuery()
        ->getResult();
  }
  
  public function getElementByStateLock() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    return $repository->createQueryBuilder('u')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'lock')
        ->getQuery()
        ->getArrayResult();
  } 
  
  public function getElementByStateDeny() {
    $repository = $this->container->get('doctrine')->getRepository('SogedialUserBundle:User');
    return $repository->createQueryBuilder('u')
        ->where('u.statut LIKE :statut')
        ->setParameter('statut', 'deny')
        ->getQuery()
        ->getArrayResult();
  }   
  
  public function getElementsByState($state) {
    $method_name = 'getElementByState' . ucfirst($state);

    if (method_exists($this, $method_name)) {
      return $this->$method_name();
    }

    return array();
  }
}