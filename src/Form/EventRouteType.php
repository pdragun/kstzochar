<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventRoute;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/** @extends AbstractType<EventRoute> */
class EventRouteType extends AbstractType
{
    /**
     * The fields are not required in HTML, so the user can leave a route row empty and
     * RemoveEmptyRoutesSubscriber skips it. A row with only a title or a length fails validation.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'form.eventRouteType.title', 'empty_data' => '', 'required' => false],
            )
            ->add(
                'length',
                IntegerType::class,
                ['label' => 'form.eventRouteType.length', 'required' => false],
            )
            ->add(
                'elevation',
                IntegerType::class,
                ['label' => 'form.eventRouteType.elevation', 'required' => false],
            )
            // ->add('createdAt')
            // ->add('gpxSlug')
            // ->add('eventDate')
            // ->add('eventInvitations')
            // ->add('eventChronicles')
        ;

        // EventRoute::setLength() takes an int, so an empty length becomes a form error instead of a null
        $builder->get('length')->addModelTransformer(new CallbackTransformer(
            static fn (?int $length): ?int => $length,
            static fn (?int $length): int => $length ?? throw new TransformationFailedException(
                'The route length is empty.',
                invalidMessage: 'This value should not be blank.',
            ),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRoute::class,
        ]);
    }
}
