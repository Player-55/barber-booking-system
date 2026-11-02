<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarberDayOff extends Model
{
    protected $table = 'barber_days_off';

    protected $fillable = ['barber_id', 'date', 'reason'];

    protected $casts = ['date' => 'date'];

    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }
}
