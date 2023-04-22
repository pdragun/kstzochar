<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventChronicle;
use App\Entity\SportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class EventChronicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'form.eventChronicleType.title'],
            )
            // ->add('slug')
            ->add(
                'summary',
                TextareaType::class,
                ['label' => 'form.eventChronicleType.summary'],
            )
            ->add(
                'content',
                CKEditorType::class,
                ['label' => 'form.eventChronicleType.content'],
            )
            // ->add('publishedAt')
            ->add(
                'startDate',
                DateTimeType::class,
                [
                    'label' => 'form.eventChronicleType.startDate',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ],
            )
            ->add(
                'endDate',
                DateTimeType::class,
                [
                    'label' => 'form.eventChronicleType.endDate',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'required' => false,
                ],
            )
            // ->add('createdAt')
            ->add(
                'photoAlbumG',
                UrlType::class,
                [
                    'label' => 'form.eventChronicleType.photoAlbumG',
                    'required' => false,
                ],
            )
            // ->add('publish')
            // ->add('modifiedAt')
            // ->add('createdBy', EntityType::class, [
            //    'class'        => User::class,
            //    'choice_label' => 'displayName'
            // ])
            // ->add('event', EntityType::class, [
            //    'class'        => Event::class,
            //    'choice_label' => 'id'
            // ])
            ->add(
                'sportType',
                EntityType::class,
                [
                    'class' => SportType::class,
                    'choice_label' => 'title',
                    'choice_value' => 'slug',
                    'label' => 'form.eventChronicleType.sportType',
                    'multiple' => true,
                    'expanded' => true,
                ],
            )
            // ->add('authorBy', EntityType::class, [
            //    'class'        => User::class,
            //    'choice_label' => 'displayName'
            // ])
            ->add(
                'routes',
                CollectionType::class,
                [
                    'label' => 'form.eventChronicleType.routes',
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
                ['label' => 'form.eventChronicleType.save'],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventChronicle::class,
       ]);
    }
}
