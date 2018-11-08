<?php

namespace Sogedial\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\SiteBundle\Repository\ZoneRepository;

class SetupClientOptionsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montantFranco', 'text', array(
                'required' => false,
                'label' => 'Montant franco'
            ))
            ->add('flagFranco', 'choice', array(
                'label' => 'Bloquer le client sur le franco',
                'choices' => array(1 => 'Oui', 0 => 'Non'),
                'expanded' => true,
                'multiple' => false
            ))
            ->add('cgvCpv', 'choice', array(
                'label' => 'Type de condition',
                'choices' => array(1 => 'CCV', 0 => 'CPV'),
                'expanded' => true,
                'multiple' => false
            ))
            ->add('alreadySigned', 'choice', array(
                'label' => 'CCV et CPV déjà signées',
                'choices' => array(1 => 'OUI', 0 => 'NON'),
                'expanded' => true,
                'multiple' => false
            ));

        if($this->ambient){
            $builder->add('zoneSec', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function(ZoneRepository $zr)
                {
                    return $zr->getListZonesByEntreprise($this->codeEntreprise, 'SEC');
                },
                'required' => true,
                'label' => 'Zone Sec du client'
            ));
        }
        if($this->positiveCold){
            $builder->add('zoneFrais', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function(ZoneRepository $zr)
                {
                    return $zr->getListZonesByEntreprise($this->codeEntreprise, 'FRAIS');
                },
                'required' => true,
                'label' => 'Zone Frais du client'
            ));
        }

        if($this->negativeCold){
            $builder->add('zoneSurgele', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function(ZoneRepository $zr)
                {
                    return $zr->getListZonesByEntreprise($this->codeEntreprise, 'SURGELE');
                },
                'required' => true,
                'label' => 'Zone Surgele du client'
            ));
        }
    }

    public function setCodeEntreprise($codeEntreprise)
    {
        $this->codeEntreprise = $codeEntreprise;
    }

    public function setAmbient($ambient)
    {
        $this->ambient = $ambient;
    }

    public function setPositiveCold($positiveCold)
    {
        $this->positiveCold = $positiveCold;
    }

    public function setNegativeCold($negativeCold)
    {
        $this->negativeCold = $negativeCold;
    }

    public function getName()
    {
        return 'client_options';
    }
}