<?php

namespace Sogedial\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sogedial\UserBundle\Entity\User;
use Sogedial\SiteBundle\Entity\Entreprise;

class LoadUserData implements FixtureInterface {

  /**
   * {@inheritDoc}
   */
  public function load(ObjectManager $manager) {
    $entreprise = new Entreprise();
    $entreprise->setAdresse1('adresse1');
    $entreprise->setAdresse2('adresse2');
    $entreprise->setCodePostal('codepostal');
    $entreprise->setPays('pays');
    $entreprise->setRaisonSociale('raison sociale');
    $entreprise->setStandard('standard');
    $entreprise->setVille('ville');

    $filename = __DIR__ . '/user.json';
    $json = file_get_contents($filename);
    $users = json_decode($json, true);
    foreach ($users as $data) {
      $user = new User();
      foreach ($data as $property => $value) {
        $method = 'set' . ucfirst($property);
        $user->$method($value);
      }
      $user->setEntreprise($entreprise);
      $manager->persist($user);
      $manager->flush();
    }
  }

}