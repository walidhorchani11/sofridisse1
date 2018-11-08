<?php

namespace Sogedial\SiteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadPhotoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('attachment', 'file', array(
                'label' => 'Sélectionnez une photo',
                'mapped' => false
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'upload_photo';
    }
}