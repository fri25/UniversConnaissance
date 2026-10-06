<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\ChariowGateway;
use App\Payments\Gateways\FakeGateway;
use InvalidArgumentException;
use LogicException;

class PaymentManager
{
    /**
     * @var array<string, PaymentGateway>
     */
    private array $resolved = [];

    public function gateway(?string $name = null): PaymentGateway
    {
        $name ??= config('payment.default');

        return $this->resolved[$name] ??= $this->make($name);
    }

    private function make(string $name): PaymentGateway
    {
        $config = config("payment.gateways.{$name}");

        return match ($name) {
            'chariow' => new ChariowGateway($config),
            'fake' => app()->isProduction()
                ? throw new LogicException('Le prestataire de paiement simulé est interdit en production.')
                : new FakeGateway($config),
            default => throw new InvalidArgumentException("Prestataire de paiement inconnu : {$name}"),
        };
    }
}
