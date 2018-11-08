<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Form\EntrepriseType;

class UserTypeClient extends UserType
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
        $builder->remove('roles');
        $builder->remove('statut');
        $builder->remove('etat');
        $builder->remove('chiffreAffaire');
        $builder->remove('importationFrance');
        $builder->remove('poste');
        $builder->remove('numero1');
        $builder->remove('numero2');

        $builder->add('produitsDemande', 'textarea', array(
            'required' => false,
            'label' => false,
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('gamme', 'choice', array(
            'choices' => array('pp' => 'form.label.pp', 'md' => 'form.label.md', 'mn' => 'form.label.mn'),
            'expanded' => true,
            'multiple' => true,
            'required' => true,
            'label' => 'form.label.gamme',
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('commentaire', 'textarea', array(
            'required' => false,
            'label' => false,
            'attr' => array(
                'placeholder' => 'form.label.comment',
            ),
            'translation_domain' => 'SogedialUserBundle'
        ));

        $builder->add('chiffreAffaire', 'choice', array(
            'required' => true,
            'choices' => $user->getAvailableSales(),
            'label' => 'form.label.sales',
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('importationFrance', 'choice', array(
            'required' => true,
            'choices' => $user->getAvailableSales(),
            'label' => 'form.label.salesfrance',
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('entreprise', new EntrepriseType(), array(
            'required' => true,
            'label' => 'form.label.enterprise',
            'translation_domain' => 'SogedialUserBundle'
        ));

        $builder->add('poste', 'text', array(
            'required' => true,
            'label' => 'form.label.job',
            'translation_domain' => 'SogedialUserBundle'
        ));

        $builder->add('numero1', 'text', array(
            'required' => true,
            'label' => 'form.label.number1',
            'translation_domain' => 'SogedialUserBundle'
        ));
        $builder->add('numero2', 'text', array(
            'required' => true,
            'label' => 'form.label.number2',
            'translation_domain' => 'SogedialUserBundle'
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\UserBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sogedial_userbundle_user_client';
    }

}
