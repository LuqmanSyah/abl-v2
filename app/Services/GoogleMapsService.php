<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleMapsService
{
    public function __construct(private readonly int $retryDelayMilliseconds = 2000) {}

    public function distance(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): int {
        $key = config('services.google_maps.key');

        if (! $key) {
            throw new RuntimeException('Google Maps API key belum dikonfigurasi.');
        }

        return retry(3, function () use ($fromLatitude, $fromLongitude, $toLatitude, $toLongitude, $key): int {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$fromLatitude},{$fromLongitude}",
                'destinations' => "{$toLatitude},{$toLongitude}",
                'key' => $key,
            ])->throw();

            $distance = $response->json('rows.0.elements.0.distance.value');

            if ($response->json('status') !== 'OK'
                || $response->json('rows.0.elements.0.status') !== 'OK'
                || ! is_numeric($distance)) {
                throw new RuntimeException('Google Distance Matrix tidak mengembalikan jarak yang valid.');
            }

            return (int) round($distance);
        }, $this->retryDelayMilliseconds);
    }

    public function reverseGeocode(float $latitude, float $longitude): string
    {
        $key = config('services.google_maps.key');

        if (! $key) {
            throw new RuntimeException('Google Maps API key belum dikonfigurasi.');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $key,
        ])->throw();

        $address = $response->json('results.0.formatted_address');

        if ($response->json('status') !== 'OK' || ! is_string($address)) {
            throw new RuntimeException('Google Geocoding tidak mengembalikan alamat yang valid.');
        }

        return $address;
    }
}
