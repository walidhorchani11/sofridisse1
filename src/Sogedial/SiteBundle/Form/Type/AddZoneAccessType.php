<?php

namespace Sogedial\SiteBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Zone;

class AddZoneAccessType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', 'text', array(
                'required' => false,
                'label' => 'Nom')
            )
            ->add('lundi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Lundi')
            )
            ->add('mardi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Mardi')
            )            
            ->add('mercredi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Mercredi')
            )            
            ->add('jeudi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Jeudi')
            )            
            ->add('vendredi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Vendredi')
            )
            ->add('samedi', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Samedi')
            )
            ->add('dimanche', 'choice', array(
                'required' => true,
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Dimanche')
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        /*
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\UserBundle\Entity\User'
        ));
        */
    }

    public function getName()
    {
        return 'client_access';
    }
}