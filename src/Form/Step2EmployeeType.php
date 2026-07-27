<?php

namespace App\Form;

use App\Entity\EmployeeProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class Step2EmployeeType extends AbstractType
{
    private const array LAST_DEGREES = [
        'BEPC' => 'BEPC',
        'BAC' => 'BAC',
        'BAC+2 (BTS/DUT)' => 'BAC+2 (BTS/DUT)',
        'Licence' => 'Licence',
        'Master' => 'Master',
        'Doctorat' => 'Doctorat',
        'Autre' => 'other',
    ];

    private const array ACTIVITY_SECTORS = [
        'Agriculture' => 'Agriculture',
        'Industrie' => 'Industrie',
        'BTP' => 'BTP',
        'Commerce' => 'Commerce',
        'Transport et logistique' => 'Transport et logistique',
        'Finance, banque, assurance' => 'Finance, banque, assurance',
        'Santé' => 'Santé',
        'Éducation' => 'Éducation',
        "Technologies de l'information" => "Technologies de l'information",
        'Administration publique' => 'Administration publique',
        'Tourisme et hôtellerie' => 'Tourisme et hôtellerie',
        'Autre' => 'other',
    ];

    private const array ALLOWED_DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastDegree', ChoiceType::class, [
                'label' => 'Dernier diplôme obtenu',
                'mapped' => false,
                'choices' => self::LAST_DEGREES,
            ])
            ->add('lastDegreeOther', TextType::class, [
                'label' => 'Précisez votre diplôme',
                'mapped' => false,
                'required' => false,
            ])
            ->add('firstJobYear', IntegerType::class, [
                'label' => 'Année du premier emploi',
                'attr' => ['min' => 1950, 'max' => (int) date('Y')],
            ])
            ->add('activitySector', ChoiceType::class, [
                'label' => "Secteur d'activité",
                'mapped' => false,
                'choices' => self::ACTIVITY_SECTORS,
            ])
            ->add('activitySectorOther', TextType::class, [
                'label' => 'Précisez le secteur',
                'mapped' => false,
                'required' => false,
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'Intitulé du poste',
            ])
            ->add('currentCompany', TextType::class, [
                'label' => 'Entreprise actuelle',
            ])
            ->add('wishIntegrateGabon', ChoiceType::class, [
                'label' => 'Souhaitez-vous intégrer une entreprise ou administration au Gabon ?',
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('targetCompany', TextType::class, [
                'label' => 'Entreprise visée au Gabon',
                'required' => false,
            ])
            ->add('otherCompanies', CollectionType::class, [
                'label' => 'Autres entreprises envisagées',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: self::ALLOWED_DOCUMENT_MIME_TYPES,
                        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). Taille maximale : {{ limit }} {{ suffix }}.',
                        mimeTypesMessage: 'Formats acceptés : PDF, JPEG, PNG.',
                    ),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $employeeProfile = $event->getData();

            if (!$employeeProfile instanceof EmployeeProfile) {
                return;
            }

            $degreeChoice = $form->get('lastDegree')->getData();
            $degreeOther = $form->get('lastDegreeOther')->getData();
            $employeeProfile->setLastDegree($degreeChoice === 'other' ? $degreeOther : $degreeChoice);

            $sectorChoice = $form->get('activitySector')->getData();
            $sectorOther = $form->get('activitySectorOther')->getData();
            $employeeProfile->setActivitySector($sectorChoice === 'other' ? $sectorOther : $sectorChoice);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeProfile::class,
        ]);
    }
}
