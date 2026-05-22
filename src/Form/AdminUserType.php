<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\CallbackTransformer;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'attr' => ['class' => 'form-check-input']
            ])
        ;

        // Transform roles array to single role value and vice versa
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                // Transform from entity (array) to form (single value)
                function ($rolesAsArray) {
                    if (empty($rolesAsArray)) {
                        return null;
                    }
                    // Extract ROLE_ADMIN or ROLE_STAFF (exclude ROLE_USER)
                    foreach ($rolesAsArray as $role) {
                        if ($role === 'ROLE_ADMIN' || $role === 'ROLE_STAFF') {
                            return $role;
                        }
                    }
                    return null;
                },
                // Transform from form (single value) to entity (array)
                function ($roleAsString) {
                    return $roleAsString ? [$roleAsString] : [];
                }
            ));

        $builder
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active Account',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Enter password (min. 6 characters)'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}

