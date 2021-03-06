<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static insert(array $models) Bulk insert of models
 */
class Member extends Model
{
    protected $guarded = [];

    public function strikes()
    {
        return $this->hasMany(Strike::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }
}