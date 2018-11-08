<?php

namespace Sogedial\SiteBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EntrepriseType extends AbstractType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
            ->add('raisonSociale', 'text', array(
                'required' => true,
                'label' => 'form.label.raisonSociale',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('nom', 'text', array(
                'required' => true,
                'label' => 'form.label.nomEntreprise',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('adresse1', 'text', array(
                'required' => true,
                'label' => 'form.label.adresse1',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('adresse2', 'text', array(
                'required' => true,
                'label' => 'form.label.adresse2',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('codePostal', 'text', array(
                'required' => true,
                'label' => 'form.label.codePostal',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('ville', 'text', array(
                'required' => true,
                'label' => 'form.label.ville',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('pays', 'text', array(
                'required' => true,
                'label' => 'form.label.pays',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('standard', 'text', array(
                'required' => true,
                'label' => 'form.label.standard',
                'translation_domain' => 'SogedialSiteBundle'
            ))
    ;
  }

  /**
   * @param OptionsResolverInterface $resolver
   */
  public function setDefaultOptions(OptionsResolverInterface $resolver) {
    $resolver->setDefaults(array(
        'data_class' => 'Sogedial\SiteBundle\Entity\Entreprise'
    ));
  }

  /**
   * @return string
   */
  public function getName() {
    return 'sogedial_sitebundle_entreprise';
  }

}
