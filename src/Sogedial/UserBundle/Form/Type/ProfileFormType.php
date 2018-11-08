<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

class ProfileFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      parent::buildForm($builder, $options);
      $className = $options['data_class'];
      $user = new $className();
      
      $builder->add('prenom', 'text', array(
        'label' => 'form.label.firstname',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('nom', 'text', array(
        'label' => 'form.label.lastname',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('paysVente', 'choice', array(
        'choices' => $user->getAvailableCountry(),
        'label' => 'form.label.country',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('chiffreAffaire', 'choice', array(
        'choices' => $user->getAvailableSales(),
        'label' => 'form.label.sales',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('importationFrance', 'choice', array(
        'choices' => $user->getAvailableSales(),
        'label' => 'form.label.salesfrance',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('numero1', 'text', array(
        'required' => false,
        'label' => 'form.label.number1',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('numero2', 'text', array(
        'required' => false,
        'label' => 'form.label.number2',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('nature', 'choice', array(
        'choices' => $user->getAvailableNature(),
        'label' => 'form.label.nature',
        'translation_domain' => 'SogedialUserBundle'
      ))
      ->add('etat', 'choice', array(
        'choices' => $user->getAvailableState(),
        'label' => 'form.label.state',
        'translation_domain' => 'SogedialUserBundle'
      ))            
      ->add('poste', 'text', array(
          'required' => false,
          'label' => 'form.label.job',
          'translation_domain' => 'SogedialUserBundle'
      ));
    }

    public function getName()
    {
        return 'sogedial_user_profile';
    }
}
