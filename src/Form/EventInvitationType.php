<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventInvitation;
use App\Entity\SportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class EventInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'form.eventInvitationType.title'],
            )
            //->add('slug')
            ->add(
                'summary',
                TextareaType::class,
                ['label' => 'form.eventInvitationType.summary'],
            )
            ->add(
                'content',
                CKEditorType::class,
                ['label' => 'form.eventInvitationType.content'],
            )
            // ->add('publishedAt')
            ->add(
                'startDate',
                DateTimeType::class,
                [
                    'label' => 'form.eventInvitationType.startDate',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ],
            )
            ->add(
                'endDate',
                DateTimeType::class,
                [
                    'label' => 'form.eventInvitationType.endDate',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'required' => false,
                ],
            )
            // ->add('createdAt')
            // ->add('publish')
            // ->add('modifiedAt')
            // ->add('createdBy')
            // ->add('event')
            ->add(
                'sportType',
                EntityType::class,
                [
                    'class' => SportType::class,
                    'choice_label' => 'title',
                    'choice_value' => 'slug',
                    'label' => 'form.eventInvitationType.sportType',
                    'multiple' => true,
                    'expanded' => true,
                ],
            )
            // ->add('authorBy')
            ->add(
                'routes',
                CollectionType::class,
                [
                    'entry_type'   => EventRouteType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                    'by_reference' => false,
                    'allow_delete' => true,
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'form.eventInvitationType.save'],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventInvitation::class,
        ]);
    }
}
