<?php

namespace App\Form;

use App\Entity\Products;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class ProductsType extends AbstractType
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categoryChoices = [];
        try {
            foreach ($this->categoryRepository->findAll() as $category) {
                $categoryChoices[$category->getName()] = $category->getName();
            }
        } catch (\Throwable) {
            // categories table may be empty or migrations pending — form still loads
        }
        if ($categoryChoices === []) {
            $categoryChoices['General'] = 'General';
        }

        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('price', NumberType::class, [
                'scale' => 2,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Price is required',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Price must be greater than 0',
                    ]),
                ],
                'attr' => [
                    'step' => '0.01',
                    'min' => '0.01',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => $categoryChoices,
                'placeholder' => empty($categoryChoices) ? 'No categories available' : 'Select category',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('stock', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Stock is required',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Stock must be greater than 0',
                    ]),
                ],
                'attr' => [
                    'min' => '1',
                    'step' => '1',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'maxSizeMessage' => 'The file is too large. Maximum size is 10MB.',
                    ]),
                ],
            ])
        ;

        // Transform string price to/from float for MoneyType
        $builder->get('price')
            ->addModelTransformer(new CallbackTransformer(
                function ($priceAsString) {
                    return $priceAsString !== null && $priceAsString !== '' ? (float) $priceAsString : null;
                },
                function ($priceAsFloat) {
                    return $priceAsFloat !== null ? number_format((float) $priceAsFloat, 2, '.', '') : null;
                }
            ));

        // Transform stock to ensure it's always an integer and never null
        $builder->get('stock')
            ->addModelTransformer(new CallbackTransformer(
                function ($stockAsInt) {
                    // Entity to form: int to int (or null to 1)
                    return $stockAsInt !== null ? (int) $stockAsInt : 1;
                },
                function ($stockAsValue) {
                    // Form to entity: convert to int, default to 1 if null or empty
                    if ($stockAsValue === null || $stockAsValue === '') {
                        return 1;
                    }
                    $stock = (int) $stockAsValue;
                    return $stock > 0 ? $stock : 1;
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Products::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'product_item',
        ]);
    }
}
