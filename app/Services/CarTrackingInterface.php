<?php

namespace App\Services;

interface CarTrackingInterface
{
    public function apply($data, $periodStart, $periodEnd);
}
