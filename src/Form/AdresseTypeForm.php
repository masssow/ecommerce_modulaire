<?php

namespace App\Form;

use App\Entity\Adresse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdresseTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Libellé (ex. Domicile, Bureau)',
            ])
            ->add('line1', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('line2', TextType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Complément',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'empty_data' => '',
                'label' => 'Téléphone',
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Livraison'   => 'livraison',
                    'Facturation' => 'facturation',
                ],
                'placeholder' => 'Type d’adresse',
                'label' => 'Type',
            ])
            ->add('isDefault', CheckboxType::class, [
                'required' => false,
                'label' => 'Définir comme adresse par défaut',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adresse::class,
        ]);
    }
}
