<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'customer_name',
        'customer_phone',
        'service_id',
        'barber_id',
        'booking_date',
        'booking_time',
        'end_time',
        'total_price',
        'deposit_amount',
        'status',
        'payment_status',
    ];

    // app/Models/Booking.php
    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function barber() {
        return $this->belongsTo(Barber::class);
    }
}