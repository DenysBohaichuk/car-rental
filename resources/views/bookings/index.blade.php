@extends('layouts.main')
@section('content')
    <div>
        <form action="{{ route('bookings.index') }}" method="get" class="mb-5">
            <div class="d-flex gap-5 mb-3">
                <select name="year" class="form-select" size="5" aria-label="Year select example">
                    @for ($optYear = now()->year; $optYear >= 1990; $optYear--)
                        <option value="{{ $optYear }}" {{ $optYear == $year ? 'selected' : '' }}>
                            {{ $optYear }}
                        </option>
                    @endfor
                </select>

                <select name="month" class="form-select" size="5" aria-label="Month select example">
                    @foreach (range(1, 12) as $optMonth)
                        <option value="{{ $optMonth }}" {{ $optMonth == $month ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $optMonth)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>


        <table class="table">
            <caption>
                Monthly savings
            </caption>
            <thead>
            <tr>
                <th scope="col" colspan="9">Car</th>
                <th scope="col" colspan="4">{{ $month . '.' . $year }}</th>
            </tr>
            <tr>
                <th scope="col">id</th>
                <th scope="col">name</th>
                <th scope="col">year</th>
                <th scope="col">color</th>
                <th scope="col">brand</th>
                <th scope="col">number</th>
{{--                <th scope="col">body type</th>--}}
                <th scope="col">create</th>
                <th scope="col">car types</th>
                <th scope="col">free</th>
                <th scope="col">busy</th>
                <th scope="col">all</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($cars as $car)
                <tr>
                        <td>{{ $car->car_id }}</td>

                        @if($car->rcTranslations)
                            @foreach ($car->rcTranslations as $translation)
                                <td>{{ $translation->title }}</td>
                            @endforeach
                        @endif

                        <td>{{ $car->attribute_year }}</td>

                        <td>{{ $car->rcModel->attribute_interior_color }}</td>

                        @if($car->rcModel->rcBrand->rcTranslations)
                            @foreach ($car->rcModel->rcBrand->rcTranslations as $translation)
                                <td>{{ $translation->name }}</td>
                            @endforeach
                        @endif

                        <td>{{ $car->registration_number }}</td>

                        <td>{{ date_format($car->created_at, 'Y-m-d') }}</td>

                        <td>{{ $car->rcModel->type }}</td>

                        <td>{{ $car->freeDays }}</td>
                        <td>{{ $car->busyDays }}</td>
                        <td>{{ cal_days_in_month(CAL_GREGORIAN, $month, $year) }}</td>

                </tr>
            @endforeach

            </tbody>

        </table>
        <div>
            {{ $cars->withQueryString()->links() }}
        </div>
    </div>
@endsection
