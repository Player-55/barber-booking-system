<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barber extends Model
{
    use HasFactory;

    /**
     * Phone in WhatsApp format: "014-537 3901" -> "60145373901". Null if no phone.
     */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);
        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '6' . $digits : $digits;
    }

    protected $fillable = [
        'name',
        'specialty',
        'phone',
        'is_active',
    ];
}