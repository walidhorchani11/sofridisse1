<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Form\EntrepriseEditType;

class UserType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = $options['data_class'];
        $user = new $className();
        $statusChoice = array();
        foreach ($user->getAvailableStatus() as $status) {
            $statusChoice[$status] = $status;
        }
        $builder
            ->add('username', 'text', array(
                'required' => true,
                'label' => 'form.label.username',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('email', 'text', array(
                'required' => true,
                'label' => 'form.label.email',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('password', 'repeated', array(
                'required' => true,
                'first_name' => 'password',
                'second_name' => 'confirm',
                'type' => 'password',
                'translation_domain' => 'SogedialUserBundle',
                'first_options' => array(
                    'label' => 'form.label.password'
                ),
                'second_options' => array(
                    'label' => 'form.label.confirm'
                )
            ))
            ->add('enabled', 'hidden', array(
                'required' => false,
                'label' => 'form.label.enabled',
                'translation_domain' => 'SogedialUserBundle',
                'data' => 0,
            ))
            ->add('locked', 'hidden', array(
                'required' => false,
                'label' => 'form.label.locked',
                'translation_domain' => 'SogedialUserBundle',
                'data' => 0,
            ))
            ->add('prenom', 'text', array(
                'required' => true,
                'label' => 'form.label.firstname',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('nom', 'text', array(
                'required' => true,
                'label' => 'form.label.lastname',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('paysVente', 'choice', array(
                'choices' => $user->getAvailableCountry(),
                'required' => true,
                'label' => 'form.label.country',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('chiffreAffaire', 'choice', array(
                'choices' => $user->getAvailableSales(),
                'required' => false,
                'label' => 'form.label.salesOptinnal',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('nature', 'choice', array(
                'choices' => $user->getAvailableNature(),
                'required' => true,
                'label' => 'form.label.nature',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('importationFrance', 'choice', array(
                'choices' => $user->getAvailableSales(),
                'required' => false,
                'label' => 'form.label.salesfranceOptinnal',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('poste', 'text', array(
                'required' => false,
                'label' => 'form.label.jobOptinnal',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('numero1', 'text', array(
                'required' => false,
                'label' => 'form.label.number1Optinnal',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('numero2', 'text', array(
                'required' => false,
                'label' => 'form.label.number2Optinnal',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('etat', 'choice', array(
                'choices' => $user->getAvailableState(),
                'label' => 'form.label.state',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('roles', 'choice', array(
                'choices' => $user->getAvailableRole(),
                'multiple' => true,
                'label' => 'form.label.roles',
                'translation_domain' => 'SogedialUserBundle',
                'data' => array('ROLE_USER')
            ))
            ->add('entreprise', new EntrepriseEditType(), array(
                'required' => false,
                'label' => 'form.label.enterprise',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('locale', 'locale', array(
                'required' => true,
                'preferred_choices' => array('fr_FR'),
                'label' => 'form.label.locale',
                'translation_domain' => 'SogedialUserBundle'
            ))
            ->add('locale2', 'locale', array(
                'required' => false,
                'mapped' => false,
                'label' => 'form.label.locale2',
                'translation_domain' => 'SogedialUserBundle'
            ));
    }

    /**
     * @param \Sogedial\UserBundle\Form\Type\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\UserBundle\Entity\User',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sogedial_userbundle_user';
    }

}
