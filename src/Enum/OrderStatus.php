<?php

namespace App\Enum;

final class OrderStatus
{
    // Valeurs stockées en base
    public const NEW            = 'new';
    public const PAID           = 'paid';
    public const IN_PREPARATION = 'in_preparation';
    public const SHIPPED        = 'shipped';
    public const DELIVERED      = 'delivered';
    public const CANCELLED      = 'cancelled';
    public const REFUNDED       = 'refunded';

    /** Libellés d'affichage (value => label) pour Twig (select inline) */
    public static function map(): array
    {
        return [
            self::NEW            => 'Nouveau',
            self::PAID           => 'Payé',
            self::IN_PREPARATION => 'En préparation',
            self::SHIPPED        => 'Expédié',
            self::DELIVERED      => 'Livré',
            self::CANCELLED      => 'Annulé',
            self::REFUNDED       => 'Remboursé',
        ];
    }

    /** (label => value) pour les formulaires Symfony/EasyAdmin */
    public static function formChoices(): array
    {
        return array_flip(self::map());
    }

    public static function isValid(string $value): bool
    {
        return \in_array($value, array_keys(self::map()), true);
    }
}
