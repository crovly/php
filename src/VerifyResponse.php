<?php

declare(strict_types=1);

namespace Crovly;

class VerifyResponse
{
    public readonly bool $success;
    public readonly float $score;
    public readonly string $ip;
    public readonly string $solvedAt;

    /**
     * @param array{success: bool, score: float, ip: string, solvedAt: string} $data
     */
    public function __construct(array $data)
    {
        $this->success = (bool) ($data['success'] ?? false);
        $this->score = (float) ($data['score'] ?? 0.0);
        $this->ip = (string) ($data['ip'] ?? '');
        $this->solvedAt = (string) ($data['solvedAt'] ?? '');
    }

    /**
     * Check if the verification score meets the human threshold.
     *
     * @param float $threshold Minimum score to consider human (default 0.5)
     */
    public function isHuman(float $threshold = 0.5): bool
    {
        return $this->success && $this->score >= $threshold;
    }
}
