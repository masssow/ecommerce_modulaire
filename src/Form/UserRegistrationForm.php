<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as T;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserRegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => false,
                'attr'  => [
                    'class'       => 'stext-111 cl2 plh3 size-116 p-l-62 p-r-30',
                    'placeholder' => 'Prénom',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => false,
                'attr'  => [
                    'class'       => 'stext-111 cl2 plh3 size-116 p-l-62 p-r-30',
                    'placeholder' => 'Nom',
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => [
                    'class'       => 'stext-111 cl2 plh3 size-116 p-l-62 p-r-30',
                    'placeholder' => 'Adresse e-mail',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options'   => [
                    'label' => false,
                    'attr'  => [
                        'class'       => 'stext-111 cl2 plh3 size-116 p-l-62 p-r-30',
                        'placeholder' => 'Mot de passe',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options'  => [
                    'label' => false,
                    'attr'  => [
                        'class'       => 'stext-111 cl2 plh3 size-116 p-l-62 p-r-30',
                        'placeholder' => 'Confirmez le mot de passe',
                        'autocomplete' => 'new-password',
                    ],
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label'      => 'J’accepte les CGU',
                'mapped'     => false,
                'label_attr' => ['class' => 'stext-111 cl2 pointer'],
                'row_attr'   => ['class' => 'flex-c-m m-b-30'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
