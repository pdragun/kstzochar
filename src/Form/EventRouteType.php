<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventRoute;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventRouteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'form.eventRouteType.title',
                ])
            ->add(
                'length',
                IntegerType::class,
                ['label' => 'form.eventRouteType.length']
            )
            ->add(
                'elevation',
                IntegerType::class,
                ['label' => 'form.eventRouteType.elevation']
            )
            // ->add('createdAt')
            // ->add('gpxSlug')
            // ->add('eventDate')
            // ->add('eventInvitations')
            // ->add('eventChronicles')
            ->add('gpxFile', FileType::class, [
                'label' => 'form.eventRouteType.gpxFile',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5120k',
                        'mimeTypes' => [
                            'application/xml',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid GPX (json) document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRoute::class,
        ]);
    }
}
