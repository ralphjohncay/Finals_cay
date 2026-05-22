<?php

namespace App\Form;

use App\Entity\Orders;
use App\Entity\Users;
use App\Form\OrderItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OrdersType extends AbstractType
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
        
        $builder
            // Order Date (defaults to now, cannot be in the past - including time)
            ->add('orderDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Order Date',
                'html5' => true,
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ],
            ])

            // Total Price (calculated dynamically) - using TextType to avoid MoneyType currency symbol duplication
            ->add('totalPrice', TextType::class, [
                'required' => false,
                'disabled' => true,
                'label' => 'Total Price',
                'empty_data' => '0.00',
                'attr' => [
                    'readonly' => true,
                ],
            ]);

        // Status field is never shown - always set to pending_approval in controller

        // Collection of Order Items (relation to OrderItemType)
        $builder->add('orderItems', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Orders::class,
            'is_edit' => false, // Option to indicate if this is an edit form
        ]);
    }
}
