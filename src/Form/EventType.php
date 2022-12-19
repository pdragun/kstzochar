<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('startDate')
            ->add('endDate')
            ->add('publish')
            ->add('createdAt')
            ->add('showDate')
            ->add('modifiedAt')
            ->add('content')
            ->add('publishedAt')
            ->add('eventInvitation')
            ->add('eventChronicle')
            ->add('blog')
            ->add('createdBy')
            ->add('sportType')
            ->add('authorBy')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
