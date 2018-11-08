<?php

namespace Sogedial\SiteBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProduitImageType extends ProduitType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    parent::buildForm($builder, $options);
    $builder
            ->add('attachment', 'file', array(
                'mapped' => false,
                'label' => 'form.label.addPhoto',
                'translation_domain' => 'SogedialSiteBundle'
            ))
    ;
  }

  /**
   * @return string
   */
  public function getName() {
    return 'sogedial_sitebundle_produit_image';
  }

}
