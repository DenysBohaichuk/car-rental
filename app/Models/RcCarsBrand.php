<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RcCarsBrand extends Model
{
    use HasFactory;



    public function rcModels()
    {
        return $this->hasMany(RcCarsModel::class, 'car_brand_id');
    }

    public function rcTranslations()
    {
        return $this->hasMany(RcCarsBrandsTranslation::class, 'car_brand_id', 'car_brand_id');
    }
}
