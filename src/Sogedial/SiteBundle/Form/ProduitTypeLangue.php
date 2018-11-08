<?php

namespace Sogedial\SiteBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProduitTypeLangue extends ProduitType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    parent::buildForm($builder, $options);
    $builder
            ->add('denomination', 'textarea', array(
                'required' => true,
                'label' => 'form.label.denomination',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('ingredients', 'textarea', array(
                'required' => true,
                'label' => 'form.label.ingredients',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('description', 'textarea', array(
                'required' => true,
                'label' => 'form.label.description',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('locale', 'text', array(
                'mapped' => false,
                'disabled' => true
            ))
    ;
  }

  /**
   * @param OptionsResolverInterface $resolver
   */
  public function setDefaultOptions(OptionsResolverInterface $resolver) {
    $resolver->setDefaults(array(
        'data_class' => 'Sogedial\SiteBundle\Entity\Produit'
    ));
  }

  /**
   * @return string
   */
  public function getName() {
    return 'sogedial_sitebundle_produit';
  }

}
