<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Blog;
use App\Entity\SportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Nadpis:'])
            ->add('summary', TextareaType::class, ['label' => 'Úvod/krátky popis:'])
            // ->add('slug')
            ->add('content', CKEditorType::class, ['label' => 'Text článku:'])
            // ->add('publish')
            // ->add('createdAt')
            // ->add('publishedAt')
            // ->add('modifiedAt')
            ->add('startDate', DateTimeType::class, [
                'label' => 'Dátum konania (od):',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            // ->add('section')
            // ->add('createdBy')
            // ->add('event')
            ->add('sportType', EntityType::class, [
                'class'        => SportType::class,
                'choice_label' => 'title',
                'label' => 'Typ presunu:',
                'multiple' => true,
                'expanded' => true,
            ])
            // ->add('authorBy')
            ->add('save', SubmitType::class, ['label' => 'Uložiť článok'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }
}
