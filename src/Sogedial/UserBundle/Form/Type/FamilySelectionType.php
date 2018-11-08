<?php

namespace Sogedial\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\UserBundle\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FamilySelectionType extends AbstractType
{

    protected $user;

    public function __construct(User $user = null)
    {
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('checked', 'checkbox', array('required' => false, 'attr' => array('class' => 'select')));
        $builder->add('entity_id', 'text', array('attr' => array('class' => 'entity-id')));
        $builder->add('user_id', NULL, array('required' => false, 'data' => ($this->user != null ? $this->user->getId() : ''), 'attr' => array('class' => 'user-id')));
        $builder->add('show_price', NULL, array('required' => false, 'attr' => array('class' => 'show-price')));
        $builder->add('coefficient', NULL, array('required' => false, 'attr' => array('class' => 'coefficient')));
        $builder->add('is_new', NULL, array('required' => false, 'attr' => array('class' => 'is-new')));
        $builder->add('show_promotion', NULL, array('required' => false, 'attr' => array('class' => 'show-promotion')));
        $builder->add('show_exclusivity', NULL, array('required' => false, 'attr' => array('class' => 'show-exclusivity')));
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\UserBundle\Entity\FamilySelection'
        ));
    }

    public function getName()
    {
        return 'sogedial_userbundle_family_selection';
    }

}
