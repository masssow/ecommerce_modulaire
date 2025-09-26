<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert; // ✅ ICI

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(['max' => 180]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmez le mot de passe'],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 8, max: 4096),
                ],
            ])
            // Adresse (UN seul champ)
            ->add('addr_address', TextareaType::class, [
                'label' => 'Adresse',
                'mapped' => false,
                'attr' => ['rows' => 2],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
            ])
            ->add('addr_postalCode', TextType::class, [
                'label' => 'Code postal',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 20])],
            ])
            ->add('addr_city', TextType::class, [
                'label' => 'Ville',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 100])],
            ])
            ->add('addr_country', TextType::class, [
                'label' => 'Pays',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 100])],
            ])
            ->add('addr_phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'mapped' => false,
                'constraints' => [new Assert\Length(['max' => 30])],
            ])

            // Honeypot anti-bot 
            ->add('hp', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1', 'aria-hidden' => 'true', 'class' => 'd-none'],
                'row_attr' => ['style' => 'display:none'],
            ]);
            // … tes champs d’adresse (mapped:false) avec les mêmes Assert
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
