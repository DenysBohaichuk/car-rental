<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCar extends Model
{
    use HasFactory;


    public function rcModel()
    {
        return $this->belongsTo(RcCarsModel::class, 'car_model_id', 'car_model_id');
    }

    public function rcTranslations()
    {
        return $this->hasMany(RcCarsTranslation::class, 'car_id', 'car_id');
    }

    public function rcBookings(){
        return $this->hasMany(RcBookings::class, 'car_id', 'car_id');
    }
}
