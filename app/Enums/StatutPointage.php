<?php
namespace App\Enums;

enum StatutPointage: string
{
    case PRESENCE_VALIDEE = 'PRESENCE_VALIDEE';
    case WIFI_NON_AUTORISE = 'WIFI_NON_AUTORISE';
    case ACCESS_POINT_NON_AUTORISE = 'ACCESS_POINT_NON_AUTORISE';
    case LOCALISATION_NON_CONFIRMEE = 'LOCALISATION_NON_CONFIRMEE';
    case HORS_DU_BATIMENT = 'HORS_DU_BATIMENT';
    case GPS_INDISPONIBLE = 'GPS_INDISPONIBLE';
    case SIGNAL_INSUFFISANT = 'SIGNAL_INSUFFISANT';
    case POINTAGE_DEJA_EFFECTUE = 'POINTAGE_DEJA_EFFECTUE';
    // statuts techniques complémentaires
    case COMPTE_INACTIF = 'COMPTE_INACTIF';
    case HORS_PERIODE = 'HORS_PERIODE';
    case POSITION_SUSPECTE = 'POSITION_SUSPECTE';
    case TROP_DE_TENTATIVES = 'TROP_DE_TENTATIVES';
    case DEFI_INVALIDE = 'DEFI_INVALIDE';
    case ARRIVEE_REQUISE = 'ARRIVEE_REQUISE';

    public function label(): string
    {
        return match ($this) {
            self::PRESENCE_VALIDEE => 'PRÉSENCE VALIDÉE',
            self::WIFI_NON_AUTORISE => 'WIFI NON AUTORISÉ',
            self::ACCESS_POINT_NON_AUTORISE => 'ACCESS POINT NON AUTORISÉ',
            self::LOCALISATION_NON_CONFIRMEE => 'LOCALISATION NON CONFIRMÉE',
            self::HORS_DU_BATIMENT => 'HORS DU BÂTIMENT',
            self::GPS_INDISPONIBLE => 'GPS INDISPONIBLE',
            self::SIGNAL_INSUFFISANT => 'SIGNAL INSUFFISANT',
            self::POINTAGE_DEJA_EFFECTUE => 'POINTAGE DÉJÀ EFFECTUÉ',
            self::COMPTE_INACTIF => 'COMPTE INACTIF',
            self::HORS_PERIODE => 'HORS PÉRIODE DE POINTAGE',
            self::POSITION_SUSPECTE => 'POSITION SUSPECTE',
            self::TROP_DE_TENTATIVES => 'TROP DE TENTATIVES',
            self::DEFI_INVALIDE => 'DEMANDE EXPIRÉE OU DÉJÀ UTILISÉE',
            self::ARRIVEE_REQUISE => 'ARRIVÉE NON ENREGISTRÉE',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::PRESENCE_VALIDEE => 'Félicitations ! Votre présence a été enregistrée avec succès.',
            self::WIFI_NON_AUTORISE => 'Pointage refusé : veuillez vous connecter au réseau Wi-Fi officiel de l\'UPL.',
            self::ACCESS_POINT_NON_AUTORISE => 'Pointage refusé : le point d\'accès Wi-Fi utilisé n\'est pas autorisé pour le pointage.',
            self::LOCALISATION_NON_CONFIRMEE => 'Pointage refusé : votre présence à l\'intérieur du bâtiment n\'a pas pu être confirmée. Rapprochez-vous d\'un point d\'accès et réessayez.',
            self::HORS_DU_BATIMENT => 'Pointage refusé : vous devez être physiquement présent à l\'intérieur d\'un bâtiment autorisé de l\'UPL.',
            self::GPS_INDISPONIBLE => 'Activez la localisation de votre appareil (et laissez la précision se stabiliser) pour effectuer votre pointage.',
            self::SIGNAL_INSUFFISANT => 'Pointage refusé : signal Wi-Fi insuffisant. Rapprochez-vous d\'un point d\'accès.',
            self::POINTAGE_DEJA_EFFECTUE => 'Vous avez déjà effectué votre pointage pour cette période.',
            self::COMPTE_INACTIF => 'Votre compte n\'est pas actif. Contactez l\'administration.',
            self::HORS_PERIODE => 'Le pointage n\'est pas autorisé à cette date ou à cette heure.',
            self::POSITION_SUSPECTE => 'Position incohérente détectée. Réessayez depuis un appareil non modifié ou contactez l\'administration.',
            self::TROP_DE_TENTATIVES => 'Trop de tentatives refusées. Patientez quelques minutes avant de réessayer.',
            self::ARRIVEE_REQUISE => 'Vous devez d\'abord enregistrer votre arrivée avant de pointer votre départ.',
            self::DEFI_INVALIDE => 'La vérification a expiré ou a déjà été utilisée. Relancez la vérification.',
        };
    }
}
