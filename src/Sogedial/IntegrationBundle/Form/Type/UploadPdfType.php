<?php

namespace Sogedial\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadPdfType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('attachment', 'file', array(
                'label' => false,
                'mapped' => false
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'upload_pdf';
    }
}