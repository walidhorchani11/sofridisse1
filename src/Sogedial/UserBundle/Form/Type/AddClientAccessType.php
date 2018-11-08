<?php

namespace Sogedial\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Meta;
use Sogedial\SiteBundle\Repository\MetaClientRepository;

class AddClientAccessType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array(
                'required' => true,
                'label' => 'Login'
            ))
            ->add('password', 'password', array(
                'required' => true,
                'label' => 'Mot de passe'
            ));
    }

    public function getName()
    {
        return 'client_access';
    }
}