<?php
namespace Sogedial\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProspectStep3Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateLimite', 'date', array('label' => "Date limite d'accès"))
            ->add('enseigneType', 'choice', array('label' => 'Enseigne'))
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