<?php

namespace Tests\Unit;

use App\Support\GeoDistance;
use PHPUnit\Framework\TestCase;

class GeoDistanceTest extends TestCase
{
    public function test_haversine_distance_is_accurate(): void
    {
        $this->assertSame(0, GeoDistance::meters(-6.2088, 106.8456, -6.2088, 106.8456));
        $this->assertEqualsWithDelta(111_195, GeoDistance::meters(0, 0, 1, 0), 1);
    }
}
