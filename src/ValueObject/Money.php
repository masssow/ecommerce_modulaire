<?php

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Money
{
    #[ORM\Column(name: 'price_amount', type: 'integer', options: ['unsigned' => true])]
    private int $amount;

    #[ORM\Column(name: 'price_currency', length: 3)]
    private string $currency;

    public function __construct(int $amount = 0, string $currency = 'EUR')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money::amount must be >= 0 (cents).');
        }
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    // Immuable : retourne une nouvelle instance
    public function withAmount(int $newAmount): self
    {
        return new self($newAmount, $this->currency);
    }

    public function withCurrency(string $newCurrency): self
    {
        return new self($this->amount, $newCurrency);
    }
}
