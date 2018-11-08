<?php
namespace Sogedial\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProspectStep1Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('codeClient', 'text', array('label' => 'Code client'))
            ->add('codeEnseigne', 'text', array('label' => 'Code enseigne'))
            ->add('codeTarification', 'text', array('label' => 'Code tarification'))
            ->add('codeAssortiment', 'text', array('label' => 'Code assortiment'))
            ->add('codeRegion', 'text', array('label' => 'Code region'))
            ->add('codeEntreprise', 'text', array('label' => 'Code entreprise'))
            ->add('codeMetaClient', 'text', array('label' => 'Code meta-client'))
            ->add('nom', 'text', array('label' => 'Nom', 'required' => true))
            ->add('dateDebutValidite', 'date', array('label' => 'Date de début de validité'))
            ->add('responsable1', 'text', array('label' => 'Nom du premier responsable'))
            ->add('responsable2', 'text', array('label' => 'Nom du second responsable'))
            ->add('adresse1', 'text', array('label' => 'Adresse', 'required' => true))
            ->add('adresse2', 'text', array('label' => "Complément d'adresse"))
            ->add('codePostal', 'text', array('label' => 'Code postal','required' => true))
            ->add('ville', 'text', array('label' => 'Ville', 'required' => true))
            ->add('telephone', 'text', array('label' => 'Téléphone', 'required' => true))
            ->add('fax', 'text', array('label' => 'Fax'))
            ->add('email', 'email', array('label' => 'Email', 'required' => true))
            ->add('statut', 'text', array('label' => 'Statut'))
            ->add('regroupementClient', 'text', array('label' => 'Regroupement client'))
            ->add('eActif', 'text', array('label' => 'eActif'))
            ->add('promotionsCompteur', 'integer', array('label' => 'Compteur de promotions'))
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