<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A location; as a sub-form of the invitation form an untouched form gives null instead of an empty Location
 * @extends AbstractType<Location>
 */
class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'form.locationType.name', 'required' => $options['standalone']])
            ->add('addressLocality', TextType::class, ['label' => 'form.locationType.addressLocality', 'required' => $options['standalone']])
            ->add(
                'addressCountry',
                CountryType::class,
                [
                    'label' => 'form.locationType.addressCountry',
                    'preferred_choices' => ['SK', 'CZ', 'PL', 'HU', 'AT'],
                    'empty_data' => 'SK',
                ],
            )
            ->add('streetAddress', TextType::class, ['label' => 'form.locationType.streetAddress', 'required' => false])
            ->add('postalCode', TextType::class, ['label' => 'form.locationType.postalCode', 'required' => false])
            ->add('addressRegion', TextType::class, ['label' => 'form.locationType.addressRegion', 'required' => false])
            ->add('latitude', NumberType::class, $this->coordinateOptions('form.locationType.latitude'))
            ->add('longitude', NumberType::class, $this->coordinateOptions('form.locationType.longitude'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // false: an optional sub-form of the invitation form; true: the location's own edit form
            'standalone' => false,
            'data_class' => Location::class,
            // The country always has a value, so it alone doesn't make a new location
            'empty_data' => static function (FormInterface $form): ?Location {
                foreach ($form as $name => $child) {
                    if ($name !== 'addressCountry' && $child->getData() !== null && $child->getData() !== '') {
                        return new Location();
                    }
                }

                return null;
            },
        ]);
        $resolver->setAllowedTypes('standalone', 'bool');
    }

    /** @return array<string, mixed> */
    private function coordinateOptions(string $label): array
    {
        return [
            'label' => $label,
            'help' => 'form.locationType.coordinatesHelp',
            'required' => false,
            'scale' => 6,
            'html5' => true,
            'attr' => ['step' => 'any'],
        ];
    }
}
