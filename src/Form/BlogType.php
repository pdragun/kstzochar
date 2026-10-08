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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

/** @extends AbstractType<Blog> */
class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'form.blogType.title', 'empty_data' => ''],
            )
            ->add(
                'summary',
                TextareaType::class,
                ['label' => 'form.blogType.summary', 'empty_data' => ''],
            )
            // ->add('slug')
            ->add(
                'content',
                CKEditorType::class,
                ['label' => 'form.blogType.content', 'empty_data' => ''],
            )
            // ->add('publish')
            // ->add('createdAt')
            // ->add('publishedAt')
            // ->add('modifiedAt')
            ->add(
                'startDate',
                DateTimeType::class,
                [
                    'label' => 'form.blogType.startDate',
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'constraints' => [new NotBlank()],
                ],
            )
            // ->add('section')
            // ->add('createdBy')
            // ->add('event')
            ->add(
                'sportType',
                EntityType::class,
                [
                    'class' => SportType::class,
                    'choice_label' => 'title',
                    'label' => 'form.blogType.sportType',
                    'multiple' => true,
                    'expanded' => true,
                ],
            )
            ->add(
                'authorBy',
                EntityType::class,
                [
                    'class' => User::class,
                    'choice_label' => 'displayName',
                    'label' => 'form.blogType.authorBy',
                    'placeholder' => 'form.authorByNone',
                    'required' => false,
                    'query_builder' => static fn (EntityRepository $repository): QueryBuilder => $repository
                        ->createQueryBuilder('u')
                        ->orderBy('u.displayName', 'ASC'),
                ],
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'form.blogType.save'],
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }
}
