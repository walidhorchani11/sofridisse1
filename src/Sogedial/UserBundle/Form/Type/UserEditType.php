<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserEditType extends UserType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $className = $options['data_class'];
        $user = new $className();
        $builder->remove('password');

        $builder->add('produitsDemande', 'textarea', array(
            'required' => false,
            'label' => false,
            'disabled' => true,
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('gamme', 'choice', array(
            'choices' => array('pp' => 'form.label.pp', 'md' => 'form.label.md', 'mn' => 'form.label.mn'),
            'expanded' => true,
            'multiple' => true,
            'required' => true,
            'disabled' => true,
            'label' => 'form.label.gamme',
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('commentaire', 'textarea', array(
            'required' => false,
            'label' => false,
            'disabled' => true,
            'attr' => array(
                'placeholder' => 'form.label.comment',
            ),
            'translation_domain' => 'SogedialUserBundle'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sogedial_userbundle_useredit';
    }

}
