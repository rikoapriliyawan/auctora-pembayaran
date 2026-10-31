<?php

namespace App\Services;

interface EventPublisher
{
    public function publish(string $routingKey, array $pesan): void;
}