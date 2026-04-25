<?php

class DocumentStateMachine
{
    private const TRANSITIONS = [
        'PENDING_SIGNATURE'  => ['SIGNED_UNVALIDATED'],
        'SIGNED_UNVALIDATED' => ['SIGNED_VALIDATED'],
        'SIGNED_VALIDATED'   => [],
    ];

    public function isTransitionAllowed(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
