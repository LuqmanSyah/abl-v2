<?php

namespace App\Services;

use App\Exceptions\GoogleMapsException;
use Illuminate\Support\Facades\Http;

class GoogleMapsService
{
    public function reverseGeocode(float $latitude, float $longitude): string
    {
        $key = config('services.google_maps.key');

        if (! $key) {
            throw new GoogleMapsException('Google Maps API key belum dikonfigurasi.');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $key,
        ])->throw();

        $address = $response->json('results.0.formatted_address');

        if ($response->json('status') !== 'OK' || ! is_string($address)) {
            throw new GoogleMapsException('Google Geocoding tidak mengembalikan alamat yang valid.');
        }

        return $address;
    }
}
