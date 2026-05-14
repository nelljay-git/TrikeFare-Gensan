<?php
// api/utils.php

function haversine($lat1, $lon1, $lat2, $lon2)
{
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null)
        return null;
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * asin(sqrt($a));
    return $earth_radius * $c;
}

function normalizeName($name)
{
    return trim(preg_replace('/[^a-z0-9\s]/', '', strtolower($name)));
}

function calculateNameMatchScore($query, $target)
{
    if (empty($query) || empty($target))
        return 0;
    $q = normalizeName($query);
    $t = normalizeName($target);
    if ($q === $t)
        return 100;

    similar_text($q, $t, $percent);

    $qWords = array_filter(explode(' ', $q), function ($w) {
        return strlen($w) > 2;
    });
    $tWords = array_filter(explode(' ', $t), function ($w) {
        return strlen($w) > 2;
    });

    $matches = 0;
    foreach ($qWords as $qw) {
        foreach ($tWords as $tw) {
            if ($qw === $tw || strpos($tw, $qw) !== false || strpos($qw, $tw) !== false) {
                $matches++;
                break;
            }
        }
    }

    $wordScore = count($qWords) > 0 ? ($matches / count($qWords)) * 100 : 0;
    return max($percent, $wordScore);
}

function isRouteMatch($origin1, $originLat1, $originLng1, $origin2, $originLat2, $originLng2,
                      $dest1, $destLat1, $destLng1, $dest2, $destLat2, $destLng2,
                      $radiusThresholdKm = 0.5)
{
    // Evaluate Origin
    $origNameScore = calculateNameMatchScore($origin1, $origin2);
    $origDistKm = haversine($originLat1, $originLng1, $originLat2, $originLng2);

    $origIsMatch = false;
    $origConf = 0;
    if ($origNameScore >= 60) {
        $origIsMatch = true;
        $origConf = $origNameScore;
    } else if ($origDistKm !== null && $origDistKm <= $radiusThresholdKm) {
        $origIsMatch = true;
        $origConf = max(0, 100 - ($origDistKm * (100 / $radiusThresholdKm)));
    } else if (empty($origin1) && $originLat1 === null) {
        $origIsMatch = true;
        $origConf = 50;
    }

    // Evaluate Destination
    $destNameScore = calculateNameMatchScore($dest1, $dest2);
    $destDistKm = haversine($destLat1, $destLng1, $destLat2, $destLng2);

    $destIsMatch = false;
    $destConf = 0;
    if ($destNameScore >= 60) {
        $destIsMatch = true;
        $destConf = $destNameScore;
    } else if ($destDistKm !== null && $destDistKm <= $radiusThresholdKm) {
        $destIsMatch = true;
        $destConf = max(0, 100 - ($destDistKm * (100 / $radiusThresholdKm)));
    }

    if ($origIsMatch && $destIsMatch) {
        return ($origConf + $destConf) / 2;
    }
    return 0;
}

function getPercentile($data, $percentile)
{
    if (count($data) === 0)
        return 0;

    $index = ($percentile / 100) * (count($data) - 1);

    if (floor($index) == $index) {
        return $data[(int) $index];
    }

    $floor = floor($index);
    $ceil = ceil($index);
    $fraction = $index - $floor;

    return $data[$floor] + ($data[$ceil] - $data[$floor]) * $fraction;
}
