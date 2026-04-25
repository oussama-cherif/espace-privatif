<?php

use PHPUnit\Framework\TestCase;

class DocumentStateMachineTest extends TestCase
{
    private DocumentStateMachine $machine;

    protected function setUp(): void
    {
        $this->machine = new DocumentStateMachine();
    }

    public function testTransitionPendingVersUnvalidatedAutorisee(): void
    {
        $this->assertTrue(
            $this->machine->isTransitionAllowed('PENDING_SIGNATURE', 'SIGNED_UNVALIDATED')
        );
    }

    public function testTransitionUnvalidatedVersValidatedAutorisee(): void
    {
        $this->assertTrue(
            $this->machine->isTransitionAllowed('SIGNED_UNVALIDATED', 'SIGNED_VALIDATED')
        );
    }

    public function testTransitionValidatedVersPendingInterdite(): void
    {
        $this->assertFalse(
            $this->machine->isTransitionAllowed('SIGNED_VALIDATED', 'PENDING_SIGNATURE')
        );
    }

    public function testTransitionPendingVersValidatedInterdite(): void
    {
        $this->assertFalse(
            $this->machine->isTransitionAllowed('PENDING_SIGNATURE', 'SIGNED_VALIDATED')
        );
    }

    public function testTransitionUnvalidatedVersPendingInterdite(): void
    {
        $this->assertFalse(
            $this->machine->isTransitionAllowed('SIGNED_UNVALIDATED', 'PENDING_SIGNATURE')
        );
    }

    public function testEtatInconnuRetourneFalse(): void
    {
        $this->assertFalse(
            $this->machine->isTransitionAllowed('ETAT_INEXISTANT', 'PENDING_SIGNATURE')
        );
    }
}
