<?php

use PHPUnit\Framework\TestCase;

class HashVerificationTest extends TestCase
{
    public function testHashIdentiquesValides(): void
    {
        $hash = hash('sha256', 'contenu du document pdf');

        $this->assertTrue(hash_equals($hash, $hash));
    }

    public function testHashDifferentsInvalides(): void
    {
        $hashOriginal = hash('sha256', 'contenu original');
        $hashModifie  = hash('sha256', 'contenu modifié');

        $this->assertFalse(hash_equals($hashOriginal, $hashModifie));
    }

    public function testHashChangeSiDocumentModifie(): void
    {
        $contenu        = 'contenu du document original';
        $contenuModifie = $contenu . ' modifié';

        $this->assertNotEquals(
            hash('sha256', $contenu),
            hash('sha256', $contenuModifie)
        );
    }

    public function testHashEst64Caracteres(): void
    {
        $hash = hash('sha256', 'un document pdf quelconque');

        $this->assertEquals(64, strlen($hash));
    }
}
