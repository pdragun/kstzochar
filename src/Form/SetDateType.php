<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                // 'constraints' => new Type(),
                'label' => 'Dátum konania podujatia',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('save', SubmitType::class, ['label' => $options['save_button_label']]);
    }
   

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'save_button_label' => 'Click me',
        ]);
    }
}
