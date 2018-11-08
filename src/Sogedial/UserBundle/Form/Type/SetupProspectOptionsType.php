<?php

namespace Sogedial\UserBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Sogedial\SiteBundle\Repository\ZoneRepository;

class SetupProspectOptionsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montantFranco', 'text', array(
                'required' => false,
                'label' => 'Montant franco'
            ))
        ;

        if ($this->ambient) {
            $builder->add('zoneSec', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function (ZoneRepository $zr) {
                    return $zr->getListZonesByEntreprise($this->codeEntreprise, 'SEC');
                },
                'required' => true,
                'label' => 'Zone Sec du client'
            ));
        }
        if ($this->positiveCold) {
            $builder->add('zoneFrais', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function (ZoneRepository $zr) {
                    return $zr->getListZonesByEntreprise($this->codeEntreprise, 'FRAIS');
                },
                'required' => true,
                'label' => 'Zone Frais du client'
            ));
        }

        if ($this->negativeCold) {
            $builder->add('zoneSurgele', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Zone',
                'query_builder' => function (ZoneRepository $zr) {
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
        return 'prospect_options';
    }
}