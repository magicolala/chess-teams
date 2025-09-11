<?php

namespace App\Service;

final class PseudoGenerator
{
    /**
     * @var string[]
     */
    private array $pseudos = [
        "LeMatteurFou", "LaTourSansPitié", "PionFurtif", "LeCavalierMasqué",
        "FouDuRoi", "LaDameFatale", "PetitRoc", "GrandRoc", "MatEnUn",
        "Blitzman", "BulletKid", "LeSacrificeFou", "PionRoyal", "TourDeForce",
        "LaCaseNoire", "CavalierBondissant", "FouMagique", "MatDuBerger",
        "ZugzwangMan", "RoiEnDanger", "LaDameNoire", "TourVengeresse",
        "PatPatrouille", "LePatriote", "L’ÉchecEtMat", "PionCarnivore",
        "CavalierDesOmbres", "FouVolant", "RoiPiégé", "LaStratégie",
        "TourDeFer", "DameInfernale", "PetitPion", "SacrificeDeDame",
        "MatInvisible", "RoiFantôme", "TourBlanche", "FouNocturne",
        "LaCaseBlanche", "ÉchecFatal", "LeGambitRoi", "FouDuVillage",
        "CavalierFou", "TourMobile", "RoiErrant", "PionFantôme",
        "MatDesFous", "LeZugZug", "RoiDuBlitz", "TourDestructrice",
        "PatForcé", "LeCavalierRieur", "DameDesOmbres", "RoiCaché",
        "PionDeFer", "FouRapide", "MatExplosif", "TourSpectrale",
        "RoiSansCouronne", "DamePoison", "SacrificeFou", "PetitZug",
        "PionSansFin", "TourTitan", "FouSansTête", "MatUltime",
        "RoiSacrifié", "LaDameEnFeu", "TourAssassine", "PatRoyal",
        "MatBlanc", "MatNoir", "LeRoiNu", "FouPerdu",
        "CavalierErrant", "LaDameRouge", "TourMystique", "SacrificeDeCavalier",
        "PionFou", "TourRapide", "LePatinVolant", "FouRieur",
        "ÉchecRoyal", "LaDameGlaciale", "CavalierDeGlace", "TourArdent",
        "MatDuCavalier", "FouInvisible", "LeRoiFatigué", "TourDesOmbres",
        "DameLégendaire", "PionImmortel", "SacrificeSuprême", "FouRoyal",
        "CavalierDeNuit", "PatSansFin", "LeRoiSansÉchec", "TourMagique",
        "LaDameDivine", "PionMystère", "LeBlitzRoyal", "FouInfernal",
    ];

    public function getRandomPseudo(): string
    {
        return $this->pseudos[array_rand($this->pseudos)];
    }
}
