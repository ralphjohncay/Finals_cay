<?php

namespace App\Form;

use App\Entity\Services;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Service Name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'USD',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Repair' => 'Repair',
                    'Customization' => 'Customization',
                    'Retail' => 'Retail',
                ],
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Active?',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Services::class,
        ]);
    }
}
