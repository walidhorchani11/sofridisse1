<?php

namespace Sogedial\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\SiteBundle\Repository\ZoneRepository;
use Sogedial\SiteBundle\Entity\Meta;
use Sogedial\SiteBundle\Repository\MetaClientRepository;

class CredentialsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'password', array(
                'required' => true,
                'label' => 'Nouveau mot de passe :'
            ))
        ;
    }

    public function setCodeEntreprise($codeEntreprise)
    {
        $this->codeEntreprise = $codeEntreprise;
    }

    public function getName()
    {
        return 'client_credentials';
    }
}