<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventRoute;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventRouteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
            ])
            ->add('length')
            ->add('elevation')
            // ->add('createdAt')
            ->add('gpxSlug')
            // ->add('eventDate')
            // ->add('eventInvitations')
            // ->add('eventChronicles')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRoute::class,
        ]);
    }
}
