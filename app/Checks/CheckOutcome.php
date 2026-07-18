<?php

declare(strict_types=1);

namespace App\Checks;

use App\Enums\CheckStatus;

/**
 * Immutable result of a single probe: whether it succeeded, how long it took,
 * and — on failure — a short human-readable reason.
 */
final readonly class CheckOutcome
{
    public function __construct(
        public CheckStatus $status,
        public ?int $latencyMs = null,
        public ?string $error = null,
    ) {}

    public static function ok(int $latencyMs): self
    {
        return new self(CheckStatus::Ok, $latencyMs);
    }

    public static function failed(string $error, ?int $latencyMs = null): self
    {
        return new self(CheckStatus::Failed, $latencyMs, mb_substr($error, 0, 500));
    }
}
