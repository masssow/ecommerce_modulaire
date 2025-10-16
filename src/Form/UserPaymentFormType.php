<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserPaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class UserPaymentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('gateway', ChoiceType::class, [
            //     'choices' => ['Stripe' => 'stripe', 'PayPal' => 'paypal'],
            //     'label' => 'Passerelle',
            // ])
            ->add('brand', TextType::class, ['label' => 'Marque (ex: Visa)', 'required' => false])
            ->add('last4', TextType::class, ['label' => '4 derniers chiffres', 'required' => false])
            ->add('expMonth', IntegerType::class, ['label' => 'Mois expiration', 'required' => false])
            ->add('expYear', IntegerType::class, ['label' => 'Année expiration', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UserPaymentMethod::class]);
    }
}
