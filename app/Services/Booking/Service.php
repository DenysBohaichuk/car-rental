<?php

namespace App\Services\Booking;

use App\Models\RcBookings;
use App\Models\RcCar;
use Carbon\Carbon;

class Service
{
    public function getBookingsForPeriod($year, $month)
    {
        $startDate = Carbon::create($year, $month);
        $endDate = $startDate->copy()->endOfMonth();


        return RcBookings::where('status', '=', '1')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();
    }

    public function getCarsForBookings($bookings, $startDate, $endDate)
    {
        $carIds = $bookings->pluck('car_id')->unique();

        return RcCar::whereIn('car_id', $carIds)
            ->where('company_id', '=', '1')
            ->where('status', '=', '1')
            ->where('is_deleted', '!=', '1')
            ->with([
                'rcBookings' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', '=', '1')
                        ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate]);
                    })
                        ->orderBy('start_date', 'asc');
                },
                'rcTranslations' => function ($query) {
                    $query->where('lang', '=', 'en');
                },
                'rcModel.rcTranslations' => function ($query) {
                    $query->where('lang', '=', 'en');
                },
                'rcModel.rcBrand.rcTranslations' => function ($query) {
                    $query->where('lang', '=', 'en');
                }
            ])
            ->orderBy('car_id', 'asc')
            ->paginate(10);
    }
}
