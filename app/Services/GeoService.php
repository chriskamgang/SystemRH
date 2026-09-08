<?php

namespace App\Services;

use App\Models\Arret;
use App\Models\Lieu;

/**
 * Calculs de proximite geographique.
 *
 * Le controle de zone (regle 3.4) s'appuie sur PostGIS quand il est disponible,
 * et retombe sur une formule de Haversine en PHP sinon, pour rester portable.
 */
class GeoService
{
    private const RAYON_TERRE_METRES = 6371000;

    /** Distance en metres entre deux points, en projection spherique. */
    public function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return self::RAYON_TERRE_METRES * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Distance entre une position et un arret de reference. */
    public function distanceArret(Arret $arret, float $latitude, float $longitude): float
    {
        return $this->distance($arret->latitude, $arret->longitude, $latitude, $longitude);
    }

    /**
     * Le chauffeur est-il physiquement dans la zone de l'arret ?
     * C'est la condition d'activation des boutons d'etape (regle 3.4).
     */
    public function dansZone(Arret $arret, float $latitude, float $longitude): bool
    {
        return $this->distanceArret($arret, $latitude, $longitude) <= $arret->rayon_validation_metres;
    }

    /** Distance entre une position et un lieu du reseau. */
    public function distanceLieu(Lieu $lieu, float $latitude, float $longitude): float
    {
        return $this->distance($lieu->latitude, $lieu->longitude, $latitude, $longitude);
    }

    /** Le chauffeur se trouve-t-il dans la zone de validation du lieu ? */
    public function dansZoneLieu(Lieu $lieu, float $latitude, float $longitude): bool
    {
        return $this->distanceLieu($lieu, $latitude, $longitude) <= $lieu->rayon_validation_metres;
    }
}
