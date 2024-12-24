<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => 'nullable|integer|min:1970|max:' . now()->year,
            'month' => 'nullable|integer|min:1|max:12',
        ];
    }

    public function getYear()
    {
        return $this->input('year', now()->year);
    }

    public function getMonth()
    {
        return $this->input('month', now()->month);
    }

    public function getPeriod()
    {
        $year = $this->getYear();
        $month = $this->getMonth();

        $startDate = Carbon::create($year, $month);
        $endDate = $startDate->copy()->endOfMonth();

        return [$startDate, $endDate];
    }
}
