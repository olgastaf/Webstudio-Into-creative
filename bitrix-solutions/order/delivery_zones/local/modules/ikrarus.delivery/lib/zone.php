<?php

namespace Ikrarus\Delivery;

final class Zone
{
    /**
     * Ищет активную зону по координатам.
     *
     * $longitude — долгота.
     * $latitude  — широта.
     */
    public static function findByCoordinates(
        float $longitude,
        float $latitude
    ): ?array {
        $dataClass = ZoneTable::getDataClass();

        $result = $dataClass::getList([
            'order' => [
                'UF_PRIORITY' => 'ASC',
                'UF_SORT' => 'ASC',
                'ID' => 'ASC',
            ],
            'filter' => [
                '=UF_ACTIVE' => 1,
            ],
            'select' => [
                'ID',
                'UF_NAME',
                'UF_ZONE_NUMBER',
                'UF_GEOJSON',
                'UF_PRICE',
                'UF_FREE_FROM',
                'UF_ACTIVE',
                'UF_PRIORITY',
                'UF_SORT',
            ],
        ]);

        while ($zone = $result->fetch()) {
            $geoJson = json_decode(
                (string) $zone['UF_GEOJSON'],
                true
            );

            if (!is_array($geoJson)) {
                continue;
            }

            if (($geoJson['type'] ?? '') !== 'Polygon') {
                continue;
            }

            $rings = $geoJson['coordinates'] ?? [];

            if (
                !is_array($rings)
                || empty($rings[0])
            ) {
                continue;
            }

            $outerRing = $rings[0];

            if (
                self::pointInRing(
                    $longitude,
                    $latitude,
                    $outerRing
                )
            ) {
                $insideHole = false;

                foreach (array_slice($rings, 1) as $hole) {
                    if (
                        self::pointInRing(
                            $longitude,
                            $latitude,
                            $hole
                        )
                    ) {
                        $insideHole = true;
                        break;
                    }
                }

                if ($insideHole) {
                    continue;
                }

                return $zone;
            }
        }

        return null;
    }

    /**
     * Проверка точки внутри линейного кольца GeoJSON.
     *
     * Координата GeoJSON:
     * [0] = longitude
     * [1] = latitude
     */
    private static function pointInRing(
        float $longitude,
        float $latitude,
        array $ring
    ): bool {
        $inside = false;
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for (
            $i = 0, $j = $count - 1;
            $i < $count;
            $j = $i++
        ) {
            if (
                !isset($ring[$i][0], $ring[$i][1])
                || !isset($ring[$j][0], $ring[$j][1])
            ) {
                continue;
            }

            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];

            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            $intersects = (
                (($yi > $latitude) !== ($yj > $latitude))
                && (
                    $longitude
                    < (
                        ($xj - $xi)
                        * ($latitude - $yi)
                        / (($yj - $yi) ?: 1e-12)
                    )
                    + $xi
                )
            );

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}