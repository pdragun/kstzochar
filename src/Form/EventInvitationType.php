<?php declare(strict_types=1);

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Nadpis:'])
            //->add('slug')
            ->add('summary', TextareaType::class, ['label' => 'Úvod/krátky popis:'])
            ->add('content', CKEditorType::class, ['label' => 'Text kroniky:'])
            // ->add('publishedAt')
            ->add('startDate', DateTimeType::class, [
                'label' => 'Dátum konania (od):',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Dátum konania (do):',
                'widget' => 'single_text',
                'required' => false,
            ])
            // ->add('createdAt')
            // ->add('publish')
            // ->add('modifiedAt')
            // ->add('createdBy')
            // ->add('event')
            ->add('sportType', EntityType::class, [
                'class'        => SportType::class,
                'choice_label' => 'title',
                'choice_value' => 'slug',
                'label' => 'Typ presunu:',
                'multiple' => true,
                'expanded' => true,
            ])
            // ->add('authorBy')
            ->add('routes', CollectionType::class, [
                'entry_type'   => EventRouteType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'allow_delete' => true,
            ])
            ->add('save', SubmitType::class, ['label' => 'Uložiť pozvánku'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventInvitation::class,
        ]);
    }
}
