<?php
namespace Sogedial\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', 'text', array('label' => false))
            ->add('save', 'submit', array('label' => 'Rechercher un produit'))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'catalogue_integration_research';
    }
}