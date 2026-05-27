<?php

namespace App\Form;

use App\Entity\AppNotification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'Title (optional)',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 4],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Info' => AppNotification::TYPE_INFO,
                    'Success' => AppNotification::TYPE_SUCCESS,
                    'Warning' => AppNotification::TYPE_WARNING,
                    'Important' => AppNotification::TYPE_DANGER,
                ],
            ])
            ->add('audience', ChoiceType::class, [
                'label' => 'Show to',
                'choices' => [
                    'Everyone (app + guests)' => AppNotification::AUDIENCE_ALL,
                    'Logged-in customers only' => AppNotification::AUDIENCE_CUSTOMERS,
                ],
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'Priority (higher shows first)',
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Starts at',
                'widget' => 'single_text',
            ])
            ->add('expiresAt', DateTimeType::class, [
                'label' => 'Expires at (optional)',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active (visible in app)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppNotification::class,
        ]);
    }
}
