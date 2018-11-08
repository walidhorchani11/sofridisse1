<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddClientProspectStep3Type extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateDebutValidite', 'date', array(
                'attr' => array('class' => 'js-datepicker1', 'readonly' => true),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
                'label' => 'Date de début de validité'
            ))
            ->add('dateFinValidite', 'date', array(
                'attr' => array('class' => 'js-datepicker2', 'readonly' => true),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
                'label' => 'Date de fin de validité'
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\UserBundle\Entity\User',
        ));
    }

    public function getName()
    {
        return 'client_prospect_add_step3';
    }
}