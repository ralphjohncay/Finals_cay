<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Products;
use App\Repository\ProductsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Product dropdown (auto-populated from repository)
            ->add('product', EntityType::class, [
                'class' => Products::class,
                'choice_label' => 'name',
                'placeholder' => 'Select product',
                'query_builder' => function (ProductsRepository $r) {
                    return $r->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'choice_attr' => function (?Products $product) {
                    if (!$product) return [];
                    return [
                        'data-price' => (string)$product->getPrice(),
                        'data-name' => (string)$product->getName(),
                        'data-stock' => (string)$product->getStock(),
                    ];
                },
                'required' => false,
                'label' => 'Product',
                'attr' => ['class' => 'item-product form-select'],
            ])

            // Quantity input
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'min' => 1,
                    'class' => 'item-quantity form-control',
                ],
                'empty_data' => '1',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
