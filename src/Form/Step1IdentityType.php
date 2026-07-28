<?php

namespace App\Form;

use App\Entity\Enum\AfricanCountry;
use App\Entity\IdentityDocument;
use App\Entity\Registrant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class Step1IdentityType extends AbstractType
{
    private const array PROFESSIONS = [
        'Étudiant(e)' => 'Étudiant(e)',
        'Enseignant(e)' => 'Enseignant(e)',
        'Ingénieur(e)' => 'Ingénieur(e)',
        'Médecin' => 'Médecin',
        'Infirmier(ère)' => 'Infirmier(ère)',
        'Commerçant(e)' => 'Commerçant(e)',
        'Fonctionnaire' => 'Fonctionnaire',
        'Entrepreneur(e)' => 'Entrepreneur(e)',
        'Artisan(e)' => 'Artisan(e)',
        'Avocat(e)' => 'Avocat(e)',
        'Comptable' => 'Comptable',
        'Informaticien(ne)' => 'Informaticien(ne)',
        'Ouvrier(ère)' => 'Ouvrier(ère)',
        'Sans emploi' => 'Sans emploi',
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
            // --- Identité civile ---
            ->add('country', ChoiceType::class, [
                'label' => 'Pays de résidence',
                'choices' => $this->getAfricanCountryChoices(),
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénoms',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('birthPlace', TextType::class, [
                'label' => 'Lieu de naissance',
            ])
            ->add('birthCountry', CountryType::class, [
                'label' => 'Pays de naissance',
            ])
            ->add('profession', ChoiceType::class, [
                'label' => 'Profession',
                'mapped' => false,
                'choices' => self::PROFESSIONS,
            ])
            ->add('professionOther', TextType::class, [
                'label' => 'Précisez votre profession',
                'mapped' => false,
                'required' => false,
            ])

            // --- Téléphones ---
            ->add('localPhone', TelType::class, [
                'label' => 'Téléphone local (E.164)',
            ])
            ->add('gabonContact', TelType::class, [
                'label' => 'Contact au Gabon (E.164)',
            ])

            // --- Séjour ---
            ->add('firstEntryDate', DateType::class, [
                'label' => "Date de première entrée dans le pays d'accueil",
                'widget' => 'single_text',
                'html5' => true,
            ])

            // --- Passeport ---
            ->add('passportNumber', TextType::class, [
                'label' => 'Numéro de passeport',
                'property_path' => 'identityDocument.passportNumber',
            ])
            ->add('passportIssuedAt', DateType::class, [
                'label' => 'Délivré le',
                'widget' => 'single_text',
                'html5' => true,
                'property_path' => 'identityDocument.passportIssuedAt',
            ])
            ->add('passportExpiresAt', DateType::class, [
                'label' => "Date d'expiration",
                'widget' => 'single_text',
                'html5' => true,
                'property_path' => 'identityDocument.passportExpiresAt',
            ])
            ->add('passportFile', FileType::class, [
                'label' => 'Copie du passeport',
                'mapped' => false,
                'constraints' => $this->getDocumentFileConstraints(required: true),
            ])

            // --- Carte de résident ---
            ->add('residentCardNotApplicable', CheckboxType::class, [
                'label' => 'Non concerné(e)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('residentCardNumber', TextType::class, [
                'label' => 'Numéro de la carte de résident',
                'required' => false,
                'property_path' => 'identityDocument.residentCardNumber',
            ])
            ->add('residentCardIssuedAt', DateType::class, [
                'label' => 'Délivrée le',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'property_path' => 'identityDocument.residentCardIssuedAt',
            ])
            ->add('residentCardExpiresAt', DateType::class, [
                'label' => "Date d'expiration",
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'property_path' => 'identityDocument.residentCardExpiresAt',
            ])
            ->add('residentCardFile', FileType::class, [
                'label' => 'Copie de la carte de résident',
                'mapped' => false,
                'required' => false,
                'constraints' => $this->getDocumentFileConstraints(required: false),
            ])

            // --- Carte consulaire ---
            ->add('consularCardNumber', TextType::class, [
                'label' => "Numéro de la carte consulaire",
                'property_path' => 'identityDocument.consularCardNumber',
            ])
            ->add('consularCardIssuedAt', DateType::class, [
                'label' => 'Délivrée le',
                'widget' => 'single_text',
                'html5' => true,
                'property_path' => 'identityDocument.consularCardIssuedAt',
            ])
            ->add('consularCardExpiresAt', DateType::class, [
                'label' => "Date d'expiration",
                'widget' => 'single_text',
                'html5' => true,
                'property_path' => 'identityDocument.consularCardExpiresAt',
            ])
            ->add('consularCardFile', FileType::class, [
                'label' => 'Copie de la carte consulaire',
                'mapped' => false,
                'constraints' => $this->getDocumentFileConstraints(required: true),
            ])
        ;

        // Fusionne le choix "profession" + son champ libre "Autre", et applique
        // la case "Non concerné" de la carte de résident, avant la validation.
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $registrant = $event->getData();

            if (!$registrant instanceof Registrant) {
                return;
            }

            $professionChoice = $form->get('profession')->getData();
            $professionOther = $form->get('professionOther')->getData();
            $registrant->setProfession($professionChoice === 'other' ? $professionOther : $professionChoice);

            if ($form->get('residentCardNotApplicable')->getData()) {
                $registrant->getIdentityDocument()
                    ->setResidentCardNumber(null)
                    ->setResidentCardIssuedAt(null)
                    ->setResidentCardExpiresAt(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Registrant::class,
            'empty_data' => static fn (FormInterface $form): Registrant => new Registrant(new IdentityDocument()),
        ]);
    }

    /**
     * @return array<string, string> libellé => code ISO 3166-1 alpha-2
     */
    private function getAfricanCountryChoices(): array
    {
        $choices = [];
        foreach (AfricanCountry::cases() as $country) {
            $choices[Countries::getName($country->value, 'fr')] = $country->value;
        }
        ksort($choices);

        return $choices;
    }

    /**
     * @return list<File|NotBlank>
     */
    private function getDocumentFileConstraints(bool $required): array
    {
        $constraints = [
            new File(
                maxSize: '5M',
                mimeTypes: self::ALLOWED_DOCUMENT_MIME_TYPES,
                maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). Taille maximale : {{ limit }} {{ suffix }}.',
                mimeTypesMessage: 'Formats acceptés : PDF, JPEG, PNG.',
            ),
        ];

        if ($required) {
            array_unshift($constraints, new NotBlank(message: 'Ce document est requis.'));
        }

        return $constraints;
    }
}
