<?php

namespace App\Twig;

use App\Enum\OrderStatus;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('twig.extension')]
class OrderStatusExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('order_status_choices', fn() => OrderStatus::map()),
        ];
    }
}
    