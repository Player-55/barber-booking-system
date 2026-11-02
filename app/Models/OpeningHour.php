<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OpeningHour extends Model
{
    protected $fillable = ['day_of_week', 'is_closed', 'open_time', 'close_time', 'break_start', 'break_end'];

    protected $casts = ['is_closed' => 'boolean'];

    public const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** "Monday", "Tuesday", ... */
    public function dayName(): string
    {
        return self::DAY_NAMES[$this->day_of_week];
    }

    /** "19:00" -> "7:00 PM" */
    public static function niceTime(?string $time): string
    {
        return $time ? Carbon::createFromFormat('H:i', $time)->format('g:i A') : '';
    }

    /** All 7 days, Monday first (for display). */
    public static function weekMondayFirst()
    {
        return static::all()->sortBy(fn ($h) => ($h->day_of_week + 6) % 7)->values();
    }
}
