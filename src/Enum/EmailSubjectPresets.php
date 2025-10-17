<?php

namespace App\Enum;

final class EmailSubjectPresets
{
    /**
     * value => label
     * (on met le même texte pour value et label ; le value sera copié dans "subject")
     */
    public static function map(): array
    {
        return [
            'Commande reçue – #{{ order_number }}'                               => 'Commande reçue – #{{ order_number }}',
            'Paiement confirmé – #{{ order_number }}'                            => 'Paiement confirmé – #{{ order_number }}',
            'Votre commande est en cours de préparation – #{{ order_number }}'   => 'En cours de préparation – #{{ order_number }}',
            'Votre commande a été expédiée – #{{ order_number }}'                => 'Commande expédiée – #{{ order_number }}',
            'Votre commande est livrée – #{{ order_number }}'                    => 'Commande livrée – #{{ order_number }}',
            'Merci pour votre achat – #{{ order_number }}'                       => 'Remerciements – #{{ order_number }}',
            'Nous aimerions votre avis sur votre commande – #{{ order_number }}' => 'Demande d’avis – #{{ order_number }}',
            'Demande de retour reçue – #{{ order_number }}'                      => 'Demande de retour reçue – #{{ order_number }}',
            'Retour accepté – #{{ order_number }}'                               => 'Retour accepté – #{{ order_number }}',
            'Remboursement émis – #{{ order_number }}'                           => 'Remboursement émis – #{{ order_number }}',
            'Mise à jour de suivi – {{ tracking_number }}'                       => 'Mise à jour de suivi – {{ tracking_number }}',
            'Informations importantes concernant votre commande – #{{ order_number }}' => 'Informations importantes – #{{ order_number }}',
            'Problème de livraison – #{{ order_number }}'                        => 'Problème de livraison – #{{ order_number }}',
            'Confirmation de création de compte'                                  => 'Confirmation de création de compte',
            'Réinitialisation de mot de passe'                                   => 'Réinitialisation de mot de passe',
            'Votre facture – #{{ order_number }}'                                => 'Votre facture – #{{ order_number }}',
        ];
    }

    /** Pour ChoiceField (label => value) */
    public static function formChoices(): array
    {
        $choices = [];
        foreach (self::map() as $value => $label) {
            $choices[$label] = $value; // label visible => valeur insérée
        }
        return $choices;
    }
}
