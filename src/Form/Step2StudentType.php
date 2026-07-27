<?php

namespace App\Form;

use App\Entity\StudentProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class Step2StudentType extends AbstractType
{
    private const array ALLOWED_DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inTraining', CheckboxType::class, [
                'label' => 'En cours de formation',
                'required' => false,
            ])
            ->add('endOfCycle', CheckboxType::class, [
                'label' => 'Fin de cycle',
                'required' => false,
            ])
            ->add('awaitingDefense', CheckboxType::class, [
                'label' => 'En attente de soutenance',
                'required' => false,
            ])
            ->add('graduatedJobSeeking', CheckboxType::class, [
                'label' => "Diplômé(e), en recherche d'emploi",
                'required' => false,
            ])
            ->add('institution', TextType::class, [
                'label' => "Établissement",
            ])
            ->add('wishReturnGabon', ChoiceType::class, [
                'label' => 'Souhaitez-vous rentrer au Gabon ?',
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('targetAdministration', TextType::class, [
                'label' => 'Administration visée',
                'required' => false,
            ])
            ->add('otherAdministrations', CollectionType::class, [
                'label' => 'Autres administrations envisagées',
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('diplomaFile', FileType::class, [
                'label' => 'Copie du diplôme',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StudentProfile::class,
        ]);
    }
}
