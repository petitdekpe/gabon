<?php

namespace App\Form;

use App\Entity\Enum\AfricanCountry;
use App\Entity\Enum\ProfileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filtres partagés par la liste des inscrits et l'export (mêmes critères).
 */
class RegistrantFilterType extends AbstractType
{
    private const array DOCUMENT_STATUSES = [
        'En cours de validité' => 'valid',
        "En cours d'expiration (J-90)" => 'warning',
        'Expirée' => 'expired',
    ];

    private const array EXPORT_FORMATS = [
        'CSV' => 'csv',
        'Excel (.xlsx)' => 'xlsx',
    ];

    /**
     * Formulaire GET sans préfixe : rend des champs "search", "country"...
     * plutôt que "registrant_filter[search]", pour des URL de filtrage lisibles
     * et partageables (?search=...&country=...) entre la liste et l'export.
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('search', TextType::class, [
                'label' => 'Recherche (nom, prénom, référence)',
                'required' => false,
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'Pays de résidence',
                'required' => false,
                'placeholder' => 'Tous les pays',
                'choices' => $this->getAfricanCountryChoices(),
            ])
            ->add('profileType', ChoiceType::class, [
                'label' => 'Profil',
                'required' => false,
                'placeholder' => 'Tous les profils',
                'choices' => [
                    'Étudiant' => ProfileType::Student->value,
                    'Salarié du privé' => ProfileType::Employee->value,
                    'Entrepreneur' => ProfileType::Entrepreneur->value,
                ],
            ])
            ->add('passportStatus', ChoiceType::class, [
                'label' => 'Statut passeport',
                'required' => false,
                'placeholder' => 'Tous les statuts',
                'choices' => self::DOCUMENT_STATUSES,
            ])
            ->add('consularStatus', ChoiceType::class, [
                'label' => 'Statut carte consulaire',
                'required' => false,
                'placeholder' => 'Tous les statuts',
                'choices' => self::DOCUMENT_STATUSES,
            ])
            ->add('dateFrom', DateType::class, [
                'label' => "Inscrit à partir du",
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('dateTo', DateType::class, [
                'label' => "Inscrit jusqu'au",
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
            ])
        ;

        if ($options['include_format']) {
            $builder->add('format', ChoiceType::class, [
                'label' => 'Format',
                'required' => true,
                'choices' => self::EXPORT_FORMATS,
                'expanded' => true,
                'data' => 'csv',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'include_format' => false,
        ]);

        $resolver->setAllowedTypes('include_format', 'bool');
    }

    /**
     * @return array<string, string>
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
}
