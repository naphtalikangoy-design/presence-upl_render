<?php
namespace App\Services\Localisation;

final class LocalizationResult
{
    public function __construct(
        public readonly string $classe,        // interieur | exterieur | inconnue
        public readonly float $confiance,      // 0..1
        public readonly string $methode,       // fingerprint_knn | ap_seul | aucune | calibration_insuffisante | hors_reference
        public readonly string $assurance,     // eleve | moyen | faible | nulle
        public readonly bool $donnees,         // des mesures existent
        public readonly bool $signalSuffisant,
        public readonly bool $instable,
        public readonly array $details = [],
    ) {}
}
