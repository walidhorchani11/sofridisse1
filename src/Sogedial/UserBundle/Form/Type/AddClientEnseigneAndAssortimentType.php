<?php

namespace Sogedial\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\SiteBundle\Entity\Tarification;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Repository\EnseigneRepository;
use Sogedial\SiteBundle\Entity\Meta;
use Sogedial\SiteBundle\Repository\MetaClientRepository;
use Sogedial\SiteBundle\Repository\Assortiment;

class AddClientEnseigneAndAssortimentType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('enseigne', 'entity', array(
            'class' => 'Sogedial\SiteBundle\Entity\Enseigne',
            'choice_label' => function ($enseigne) {
                if ($enseigne->getLibelle() != NULL) {
                    return $enseigne->getLibelle();
                }
                return $enseigne->getCode();
            },
            'required' => true,
            'query_builder' => function (EnseigneRepository $EnseigneRepository) {
                return $EnseigneRepository->getListEnseignesByRegion(4);
            },
        ));

        $builder->add('assortiment', 'entity', array(
            'class' => 'Sogedial\SiteBundle\Entity\Assortiment',
            'choice_label' => function ($assortiment) {
                return $assortiment->getValeur();
            },
            'required' => true,
            'query_builder' => function ($AssortimentRepository) {
                return $AssortimentRepository->getAllValeursQueryBuilderForRegion(4);
            },
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    public function getName()
    {
        return 'assortiment_client';
    }
}