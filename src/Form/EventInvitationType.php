<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EventInvitation;
use App\Entity\Location;
use App\Entity\SportType;
use App\Form\EventListener\RemoveEmptyRoutesSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Valid;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

/** @extends AbstractType<EventInvitation> */
class EventInvitationType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'form.eventInvitationType.title', 'empty_data' => ''],
            )
            //->add('slug')
            ->add(
                'summary',
                TextareaType::class,
                ['label' => 'form.eventInvitationType.summary', 'empty_data' => ''],
            )
            ->add(
                'content',
                CKEditorType::class,
                ['label' => 'form.eventInvitationType.content', 'empty_data' => ''],
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
            ->add(
                'location',
                EntityType::class,
                [
                    'class' => Location::class,
                    'choice_label' => 'name',
                    'label' => 'form.eventInvitationType.location',
                    'placeholder' => 'form.eventInvitationType.locationNone',
                    'help' => 'form.eventInvitationType.locationHelp',
                    'help_html' => true,
                    'help_translation_parameters' => ['%url%' => $this->urlGenerator->generate('location_list')],
                    'required' => false,
                    'query_builder' => static fn (EntityRepository $repository): QueryBuilder => $repository
                        ->createQueryBuilder('l')
                        ->orderBy('l.name', 'ASC'),
                ],
            )
            ->add(
                'newLocation',
                LocationType::class,
                [
                    'label' => 'form.eventInvitationType.newLocation',
                    'help' => 'form.eventInvitationType.newLocationHelp',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [new Valid()],
                    'row_attr' => ['class' => 'mb-3 p-3 border rounded new-location'],
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

        $builder->get('routes')->addEventSubscriber(new RemoveEmptyRoutesSubscriber());

        // A filled "new location" wins over the location picked in the select
        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            $newLocation = $event->getForm()->get('newLocation')->getData();
            $invitation = $event->getData();
            if ($newLocation instanceof Location && $invitation instanceof EventInvitation) {
                $invitation->setLocation($newLocation);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventInvitation::class,
        ]);
    }
}
