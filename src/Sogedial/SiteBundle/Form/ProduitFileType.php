<?php

namespace Sogedial\SiteBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProduitFileType extends ProduitType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    parent::buildForm($builder, $options);
    $builder
            ->add('attachment', 'file', array(
                'mapped' => false,
                'label' => 'form.label.addFile',
                'translation_domain' => 'SogedialSiteBundle'
            ))
            ->add('type', 'choice', array(
                'mapped' => false,
                'choices' => array('technique' => 'Fiche technique', 'certificat' => 'Certificat', 'argumentaire' => 'Argumentaire de vente'),
                'expanded' => false,
                'expanded' => false,
                'label' => 'form.label.fileType',
                'translation_domain' => 'SogedialSiteBundle'
            ))
    ;
  }

  /**
   * @return string
   */
  public function getName() {
    return 'sogedial_sitebundle_produit_file';
  }

}
