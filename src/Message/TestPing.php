<?php

namespace App\Message;

final class TestPing
{
    public function __construct(private string $payload) {}
    public function getPayload(): string
    {
        return $this->payload;
    }
}
