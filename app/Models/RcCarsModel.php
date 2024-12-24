<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCarsModel extends Model
{
    use HasFactory;



    public function rcCars()
    {
        return $this->hasMany(RcCar::class, 'car_model_id');
    }

    public function rcBrand()
    {
        return $this->belongsTo(RcCarsBrand::class, 'car_brand_id', 'car_brand_id');
    }

    public function rcTranslations()
    {
        return $this->hasMany(RcCarsModelsTranslation::class, 'car_model_id');
    }
}
