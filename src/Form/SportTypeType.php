<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\SportType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SportTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextType::class)
            ->add('shortcut', TextType::class)
            ->add('image', TextType::class)
            ->add('events')
            ->add('eventChronicles')
            ->add('blogs')
            ->add('eventInvitations')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SportType::class,
        ]);
    }
}
