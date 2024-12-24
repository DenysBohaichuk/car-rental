<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Services\Booking\Service;
use App\Services\CarAvailabilityTracking;

class BookingsController extends Controller
{
    public $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function __invoke(BookingRequest $request)
    {
        $year = $request->getYear();
        $month = $request->getMonth();
        [$startDate, $endDate] = $request->getPeriod();

        $bookings = $this->service->getBookingsForPeriod($year, $month);
        $cars = $this->service->getCarsForBookings($bookings, $startDate, $endDate);

        $trackingService = new CarAvailabilityTracking('9:00', '21:00', 'UTC'); //'Etc/GMT+2'
        $trackingService->apply($cars, $startDate, $endDate);

        return view('bookings.index', compact('cars', 'year', 'month'));
    }
}

