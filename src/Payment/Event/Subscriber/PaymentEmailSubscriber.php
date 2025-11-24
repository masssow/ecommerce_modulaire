<?php
namespace App\Payment\Event\Subscriber\Subscriber;

use App\Payment\Event\PaymentSucceeded;
use App\Payment\Event\PaymentFailed;
use App\Message\Email\SendOrderEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class PaymentEmailSubscriber implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $bus) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentSucceeded::class => 'onPaymentSucceeded',
            PaymentFailed::class    => 'onPaymentFailed',
        ];
    }

    public function onPaymentSucceeded(PaymentSucceeded $e): void
    {
        $this->bus->dispatch(new SendOrderEmail($e->orderId, 'paid'));
    }

    public function onPaymentFailed(PaymentFailed $e): void
    {
        $this->bus->dispatch(new SendOrderEmail($e->orderId, 'failed'));
    }
}
