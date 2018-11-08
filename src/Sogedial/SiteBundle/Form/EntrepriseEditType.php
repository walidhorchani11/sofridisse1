<?php

namespace Sogedial\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntrepriseEditType extends EntrepriseType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    parent::buildForm($builder, $options);
    $builder->remove('adresse1');
    $builder->remove('adresse2');
    $builder->remove('codePostal');
    $builder->remove('ville');
    $builder->remove('standard');
    $builder
            ->add('adresse1', 'text', array(
                'required' => false,
                'label' => 'form.label.adresse1Optinnal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('adresse2', 'text', array(
                'required' => false,
                'label' => 'form.label.adresse2Optinnal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('codePostal', 'text', array(
                'required' => false,
                'label' => 'form.label.codePostalOptinnal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('ville', 'text', array(
                'required' => false,
                'label' => 'form.label.villeOptinnal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('standard', 'text', array(
                'required' => false,
                'label' => 'form.label.standardOptinnal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
    ;
  }
  
  /**
   * @return string
   */
  public function getName() {
    return 'sogedial_sitebundle_entreprise_edit';
  }

}
