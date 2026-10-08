<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Pick the location that replaces the merged one
 * @extends AbstractType<array{target: ?Location}>
 */
class LocationMergeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Location $source */
        $source = $options['source'];

        $builder
            ->add(
                'target',
                EntityType::class,
                [
                    'class' => Location::class,
                    'choice_label' => 'name',
                    'label' => 'form.locationMergeType.target',
                    'placeholder' => 'form.locationMergeType.targetNone',
                    'constraints' => [new NotNull()],
                    'query_builder' => static fn (EntityRepository $repository): QueryBuilder => $repository
                        ->createQueryBuilder('l')
                        ->where('l != :source')
                        ->setParameter('source', $source)
                        ->orderBy('l.name', 'ASC'),
                ],
            )
            ->add('save', SubmitType::class, ['label' => 'form.locationMergeType.save', 'attr' => ['class' => 'btn-primary']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('source');
        $resolver->setAllowedTypes('source', Location::class);
    }
}
