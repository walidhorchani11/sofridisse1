<?php

namespace Sogedial\UserBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sogedial\SiteBundle\Entity\Zone;
use Sogedial\SiteBundle\Repository\ZoneRepository;
use Sogedial\SiteBundle\Entity\Tarification;
use Sogedial\SiteBundle\Entity\Enseigne;
use Sogedial\SiteBundle\Repository\EnseigneRepository;
use Sogedial\SiteBundle\Repository\TarificationRepository;
use Sogedial\SiteBundle\Entity\Meta;
use Sogedial\SiteBundle\Repository\MetaClientRepository;

class AddClientProspectStep1Type extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = $options['data_class'];
        $client = new $className();

        $builder
            ->add('nom', 'text', array(
                'required' => true,
                'label' => 'Nom',
                 'attr' => array('minlength' => 3)
            ))
            ->add('responsable1', 'text', array(
                'label' => 'Responsable',
                'required' => false
            ))
            ->add('responsable2', 'text', array(
                'label' => 'Responsable 2',
                'required' => false
            ))
            ->add('adresse1', 'text', array(
                'label' => 'Adresse',
                'required' => true
            ))
            ->add('adresse2', 'text', array(
                'label' => 'Complément adresse',
                'required' => false
            ))
            ->add('codePostale', 'text', array(
                'label' => 'Code postal',
                'required' => true,
                'attr' => array('minlength' => 5)
            ))
            ->add('ville', 'text', array(
                'label' => 'Ville',
                'required' => true
            ))
            ->add('pays', 'text', array(
                'label' => 'Pays',
                'required' => true
            ))
            ->add('telephone', 'text', array(
                'label' => 'Téléphone',
                'attr' => array('minlength' => 10, 'maxlength' => 10),
                'required' => true
            ))
            ->add('fax', 'text', array(
                'label' => 'Fax',
                'attr' => array('minlength' => 10, 'maxlength' => 10),
                'required' => false
            ))
            ->add('email', 'email', array(
                'label' => 'Email',
                'required' => true
            ))
            ->add('typologieClient', 'choice', array(
                'placeholder' => 'Choissez une typologie',
                'choices' => $client->getListTypology(),
                'label' => 'Typologie',
                'required' => true
            ))
            ->add('commentaireProspect', 'textarea', array(
                'attr' => array('cols' => '41', 'rows' => '2'),
                'label' => 'Commentaire',
                'required' => false,
                'attr' => array('maxlength' => 255)
            ));

        if ($this->tarifsTarification) {
            $builder->add('tarification', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Tarification',
                'placeholder' => 'Sélectionner une tarification',
                'choice_label' => function ($tarification) {
                    if ($tarification->getLibelle() != NULL) {
                        return $tarification->getLibelle();
                    }
                    return $tarification->getCode();
                },
                'required' => true,
                'query_builder' => function (TarificationRepository $tr) {
                    return $tr->getListTarificationsByRegionForProspect($this->region);
                },
            ));
        }

        if ($this->tarifsEnseigne) {
            $builder->add('enseigne', 'entity', array(
                'class' => 'Sogedial\SiteBundle\Entity\Enseigne',
                'placeholder' => 'Sélectionnez une enseigne',
                'choice_label' => function ($enseigne) {
                    if ($enseigne->getLibelle() != NULL) {
                        return $enseigne->getLibelle();
                    }
                    return $enseigne->getCode();
                },
                'required' => true,
                'query_builder' => function (EnseigneRepository $ens) {
                    return $ens->getListEnseignesByRegionForProspect($this->region);
                },
            ));
        }
    }

    public function setCodeEntreprise($codeEntreprise)
    {
        $this->codeEntreprise = $codeEntreprise;
    }

    public function setTarifsEnseigne($tarifsEnseigne)
    {
        $this->tarifsEnseigne = $tarifsEnseigne;
    }

    public function setTarifsTarification($tarifsTarification)
    {
        $this->tarifsTarification = $tarifsTarification;
    }

    public function setRegion($region)
    {
        $this->region = $region;
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sogedial\SiteBundle\Entity\Client',
        ));
    }

    public function getName()
    {
        return 'client_prospect_add_step1';
    }
}