<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class CarAvailabilityTracking implements CarTrackingInterface
{
    private mixed $startTime;
    private mixed $endTime;
    private int $minimumHoursDifferenceInSeconds = 32400;
    private mixed $timezone;
    private mixed $defaultTimezone;

    public function __construct($startTime = '9:00', $endTime = '21:00', $timezone = 'UTC', $defaultTimezone = 'UTC')
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timezone = $timezone;
        $this->defaultTimezone = $defaultTimezone;
    }

    private function checkFreeDays($collection, $periodStartDate, $periodEndDate): void
    {
        $collection->each(function ($car) use ($periodStartDate, $periodEndDate) {
            $occupiedDays = [];
            $prevEndDate = null; // Для збереження кінця попереднього бронювання

            $car->rcBookings->each(function ($booking) use (&$occupiedDays, &$prevEndDate, $car, $periodStartDate, $periodEndDate) {

                // Конвертація дат
                $currentStartDate = Carbon::createFromFormat('Y-m-d H:i:s', $booking->start_date)
                    ->shiftTimezone($this->defaultTimezone)->setTimezone($this->timezone);

                $currentEndDate = Carbon::createFromFormat('Y-m-d H:i:s', $booking->end_date)
                    ->shiftTimezone($this->defaultTimezone)->setTimezone($this->timezone);

                // Змінюємо межі дати бронювання, щоб вони відповідали пошуковому періоду
                $currentStartDate = $currentStartDate->lt($periodStartDate) ? $periodStartDate : $currentStartDate;
                $currentEndDate = $currentEndDate->gt($periodEndDate) ? $periodEndDate : $currentEndDate;

                // Встановлюємо межі першого дня оренди (9:00 та 21:00)
                $dayStart = Carbon::parse($currentStartDate->toDateString() . ' 09:00')->setTimezone($this->timezone);
                $dayEnd = Carbon::parse($currentStartDate->toDateString() . ' 21:00')->setTimezone($this->timezone);

                // Встановлюємо межі останнього дня оренди (9:00 та 21:00)
                $lastDayStart = Carbon::parse($currentEndDate->toDateString() . ' 09:00')->setTimezone($this->timezone);
                $lastDayEnd = Carbon::parse($currentEndDate->toDateString() . ' 21:00')->setTimezone($this->timezone);

                // Дні для поточного бронювання
                $currentOccupiedDays = [];

                if ($prevEndDate && !$prevEndDate->isSameDay($currentStartDate)) {
                    $prevEndDate = null;
                }

                // провіряємо перший та останній день оренди
                $this->handleBoundaryDays($currentStartDate, $currentEndDate, $prevEndDate, $dayStart, $dayEnd, $lastDayStart, $lastDayEnd, $occupiedDays, $currentOccupiedDays);

                // Дні між початковим і кінцевим днем вважаються повністю зайнятими
                $this->getMiddleOccupiedDays($currentStartDate, $currentEndDate, $occupiedDays, $currentOccupiedDays);
            });

            // Загальна кількість днів у періоді
            $totalPeriodDays = $periodStartDate->diffInDays($periodEndDate) + 1;

            // Вільні дні = загальні дні - зайняті дні
            $freeDays = $totalPeriodDays - count($occupiedDays);

            // Збереження результатів
            $car->busyDays = count($occupiedDays);
            $car->freeDays = $freeDays;
        });
    }

    private function setOccupiedFirst($currentStartDate, &$occupiedDays, &$currentOccupiedDays): void
    {
        $occupiedDays[$currentStartDate->toDateString()] = true;
        $currentOccupiedDays[] = $currentStartDate->toDateString();
    }

    private function setOccupiedLastDay($currentEndDate, &$occupiedDays, &$currentOccupiedDays): void
    {
        $occupiedDays[$currentEndDate->toDateString()] = true;
        $currentOccupiedDays[] = $currentEndDate->toDateString();
    }

    private function checkEndOrderForFirstDay($dayEnd, $currentStartDate, $currentEndDate, &$prevEndDate, &$occupiedDays, &$currentOccupiedDays): void
    {
        if ($dayEnd->diffInSeconds($currentEndDate) >= $this->minimumHoursDifferenceInSeconds) {
            $prevEndDate = $currentEndDate;
        } else {
            $this->setOccupiedFirst($currentStartDate, $occupiedDays, $currentOccupiedDays);
        }
    }

    private function checkEndOrderForLastDay($dayEnd, $currentEndDate, &$prevEndDate, &$occupiedDays, &$currentOccupiedDays): void
    {
        if ($dayEnd->diffInSeconds($currentEndDate) >= $this->minimumHoursDifferenceInSeconds) {
            $prevEndDate = $currentEndDate;
        } else {
            $this->setOccupiedLastDay($currentEndDate, $occupiedDays, $currentOccupiedDays);
        }
    }

    private function handleEndDateBeyondCurrentDay($currentEndDate, $lastDayStart, $lastDayEnd, &$occupiedDays, &$currentOccupiedDays, &$prevEndDate): void
    {
        // Перевіряємо, чи кінець у межах останнього дня
        if ($currentEndDate->between($lastDayStart, $lastDayEnd)) {
            if ($lastDayEnd->diffInSeconds($currentEndDate) >= $this->minimumHoursDifferenceInSeconds) {
                $prevEndDate = $currentEndDate;
            } else {
                $this->setOccupiedLastDay($currentEndDate, $occupiedDays, $currentOccupiedDays);
            }
        } else if ($currentEndDate->greaterThan($lastDayEnd)) {
            $this->setOccupiedLastDay($currentEndDate, $occupiedDays, $currentOccupiedDays);
        }
    }

    private function handleBoundaryDays($currentStartDate, $currentEndDate, &$prevEndDate, $dayStart, $dayEnd, $lastDayStart, $lastDayEnd, &$occupiedDays, &$currentOccupiedDays): void
    {
        // Перевіряємо, чи start у проміжку поточного дня
        if ($currentStartDate->between($dayStart, $dayEnd)) {

            //перевіряємо на наявність попередньої дати закінчення оренди в цей же день
            if ($prevEndDate) {
                if ($prevEndDate->diffInSeconds($currentStartDate) <= $this->minimumHoursDifferenceInSeconds) {
                    //dump("є попередня оренда.");

                    if ($currentEndDate->greaterThan($dayStart->endOfDay())) {
                        //кінець оренди не в поточному дні, тому додаємо початок дня, як зайнятий. бо до початку оренди немає 9 годин а кінець вже виходить за межі

                        // Додаємо початок дня як зайнятий
                        $this->setOccupiedFirst($currentStartDate, $occupiedDays, $currentOccupiedDays);

                        $this->handleEndDateBeyondCurrentDay(
                            $currentEndDate,
                            $lastDayStart,
                            $lastDayEnd,
                            $occupiedDays,
                            $currentOccupiedDays,
                            $prevEndDate
                        );
                    } else {
                        //Кінець оренди в межах поточного дня
                        $this->checkEndOrderForFirstDay($dayEnd, $currentStartDate, $currentEndDate, $prevEndDate, $occupiedDays, $currentOccupiedDays);
                    }
                }
            } else {
                //перевіряємо із початком дня
                if ($currentStartDate->diffInSeconds($dayStart) >= $this->minimumHoursDifferenceInSeconds) {
                    //робимо вихідний, бо між початком дня та початком оренди вже є 9 годин(пропускаємо день)

                    if ($currentEndDate->greaterThan($dayStart->endOfDay())) {
                        //кінець оренди не в поточному дні, тому додаємо початок дня, як зайнятий. бо до початку оренди немає 9 годин а кінець вже виходить за межі

                        $this->handleEndDateBeyondCurrentDay(
                            $currentEndDate,
                            $lastDayStart,
                            $lastDayEnd,
                            $occupiedDays,
                            $currentOccupiedDays,
                            $prevEndDate
                        );
                    } else {
                        //Кінець оренди в межах поточного дня
                        $this->checkEndOrderForLastDay($dayEnd, $currentEndDate, $prevEndDate, $occupiedDays, $currentOccupiedDays);
                    }

                } else {
                    // перевіряємо із кінцем оренди
                    if ($currentEndDate->greaterThan($dayStart->endOfDay())) {
                        //кінець оренди не в поточному дні, тому додаємо початок дня, як зайнятий. бо до початку оренди немає 9 годин а кінець вже виходить за межі

                        // Додаємо початок дня як зайнятий
                        $this->setOccupiedFirst($currentStartDate, $occupiedDays, $currentOccupiedDays);

                        $this->handleEndDateBeyondCurrentDay(
                            $currentEndDate,
                            $lastDayStart,
                            $lastDayEnd,
                            $occupiedDays,
                            $currentOccupiedDays,
                            $prevEndDate
                        );
                    } else {
                        //Кінець оренди в межах поточного дня
                        $this->checkEndOrderForLastDay($dayEnd, $currentEndDate, $prevEndDate, $occupiedDays, $currentOccupiedDays);
                    }
                }
            }

        }
        else {

            // Якщо start поза поточним днем
            if ($currentStartDate->lessThan($dayStart)) {

                if ($currentEndDate->greaterThan($dayStart)) {

                    // перевіряємо із кінцем оренди
                    if ($currentEndDate->greaterThan($dayStart->endOfDay())) {
                        //кінець оренди не в поточному дні, тому додаємо початок дня, як зайнятий. бо до початку оренди немає 9 годин а кінець вже виходить за межі

                        // Додаємо початок дня як зайнятий
                        $this->setOccupiedFirst($currentStartDate, $occupiedDays, $currentOccupiedDays);

                        $this->handleEndDateBeyondCurrentDay(
                            $currentEndDate,
                            $lastDayStart,
                            $lastDayEnd,
                            $occupiedDays,
                            $currentOccupiedDays,
                            $prevEndDate
                        );
                    } else {
                        //Кінець оренди в межах поточного дня
                        $this->checkEndOrderForFirstDay($dayEnd, $currentStartDate, $currentEndDate, $prevEndDate, $occupiedDays, $currentOccupiedDays);
                    }
                }
            } else {
                //якщо початок оренди після закінчення робочого дня

                $this->handleEndDateBeyondCurrentDay(
                    $currentEndDate,
                    $lastDayStart,
                    $lastDayEnd,
                    $occupiedDays,
                    $currentOccupiedDays,
                    $prevEndDate
                );
            }
        }
    }

    private function getMiddleOccupiedDays($currentStartDate, $currentEndDate, &$occupiedDays, &$currentOccupiedDays): void
    {
        $middleDate = $currentStartDate->copy()->addDay();
        while ($middleDate->lessThan($currentEndDate->copy()->startOfDay())) {
            $occupiedDays[$middleDate->toDateString()] = true;
            $currentOccupiedDays[] = $middleDate->toDateString();
            /*            dump($occupiedDays);
                        dump($currentOccupiedDays);
                        dump("День між початком і кінцем зайнятий: " . $middleDate->toDateString());*/
            $middleDate->addDay();
        }
    }

    public function apply($data, $periodStart, $periodEnd): void
    {
        $this->checkFreeDays($data, $periodStart, $periodEnd);
    }
}
