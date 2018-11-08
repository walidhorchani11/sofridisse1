<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeolocationType extends AbstractType {

    /**
    * @param FormBuilderInterface $builder
    * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('adresse1', 'text', array('label' => 'Adresse'))
            ->add('codePostale', 'text', array('label' => 'Code postal'))
            ->add('ville', 'text', array('label' => 'Ville'))
            ->add('pays', 'text', array('label' => 'Pays'))
            ->add('latitude', 'text', array('label' => 'Latitude'))
            ->add('longitude', 'text', array('label' => 'Longitude'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\SiteBundle\Entity\Client'
        ));
    }

    /**
    * @return string
    */
    public function getName() {
        return 'sogedial_userbundle_geolocation';
    }

}
