<?php

namespace App\Form;

use App\Entity\EntrepreneurProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class Step2EntrepreneurType extends AbstractType
{
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
            ->add('companyName', TextType::class, [
                'label' => "Nom de l'entreprise",
            ])
            ->add('officialCreatedAt', DateType::class, [
                'label' => 'Date de création officielle',
                'widget' => 'single_text',
                'html5' => true,
                'property_path' => 'createdAt',
            ])
            ->add('legalStatus', ChoiceType::class, [
                'label' => 'Statut juridique',
                'choices' => array_combine(EntrepreneurProfile::LEGAL_STATUSES, EntrepreneurProfile::LEGAL_STATUSES),
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
            ->add('registrationNumber', TextType::class, [
                'label' => 'RCCM / NINEA / IFU',
            ])
            ->add('hasGabonProject', ChoiceType::class, [
                'label' => 'Avez-vous un projet au Gabon ?',
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('investmentSector', TextType::class, [
                'label' => "Secteur d'investissement visé",
                'required' => false,
            ])
            ->add('hasBusinessPlan', ChoiceType::class, [
                'label' => "Disposez-vous d'un business plan ?",
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('otherTargets', CollectionType::class, [
                'label' => 'Autres cibles envisagées',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('businessPlanFile', FileType::class, [
                'label' => 'Business plan',
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
            $entrepreneurProfile = $event->getData();

            if (!$entrepreneurProfile instanceof EntrepreneurProfile) {
                return;
            }

            $sectorChoice = $form->get('activitySector')->getData();
            $sectorOther = $form->get('activitySectorOther')->getData();
            $entrepreneurProfile->setActivitySector($sectorChoice === 'other' ? $sectorOther : $sectorChoice);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EntrepreneurProfile::class,
        ]);
    }
}
