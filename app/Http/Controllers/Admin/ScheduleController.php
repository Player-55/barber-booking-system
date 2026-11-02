<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\BarberDayOff;
use App\Models\Booking;
use App\Models\OpeningHour;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    /** Opening hours + barber days off page. */
    public function index()
    {
        $hours   = OpeningHour::weekMondayFirst();
        $barbers = Barber::where('is_active', true)->orderBy('name')->get();
        $daysOff = BarberDayOff::with('barber:id,name')
            ->where('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->get();

        return view('admin.schedule', compact('hours', 'barbers', 'daysOff'));
    }

    /** Save the weekly opening hours (all 7 days at once). */
    public function updateHours(Request $request)
    {
        $request->validate([
            'hours'                 => 'required|array',
            'hours.*.open_time'     => 'nullable|date_format:H:i',
            'hours.*.close_time'    => 'nullable|date_format:H:i',
            'hours.*.break_start'   => 'nullable|date_format:H:i',
            'hours.*.break_end'     => 'nullable|date_format:H:i',
        ]);

        foreach ($request->input('hours') as $day => $row) {
            $day = (int) $day;
            if ($day < 0 || $day > 6) {
                continue;
            }

            $name     = OpeningHour::DAY_NAMES[$day];
            $isClosed = ! empty($row['is_closed']);
            $open     = $row['open_time'] ?? null;
            $close    = $row['close_time'] ?? null;
            $bStart   = $row['break_start'] ?? null;
            $bEnd     = $row['break_end'] ?? null;

            if (! $isClosed) {
                // "HH:MM" strings compare correctly as text
                if (! $open || ! $close || $open >= $close) {
                    throw ValidationException::withMessages(['hours' => "$name: closing time must be after opening time."]);
                }
                if (($bStart && ! $bEnd) || (! $bStart && $bEnd)) {
                    throw ValidationException::withMessages(['hours' => "$name: fill in both break start and break end (or leave both empty)."]);
                }
                if ($bStart && ($bStart >= $bEnd || $bStart < $open || $bEnd > $close)) {
                    throw ValidationException::withMessages(['hours' => "$name: the break must be inside opening hours, and end after it starts."]);
                }
            }

            OpeningHour::updateOrCreate(['day_of_week' => $day], [
                'is_closed'   => $isClosed,
                'open_time'   => $open ?: '10:00',
                'close_time'  => $close ?: '22:00',
                'break_start' => $bStart ?: null,
                'break_end'   => $bEnd ?: null,
            ]);
        }

        return back()->with('success', 'Opening hours saved.');
    }

    /** Add a day off for a barber. */
    public function storeDayOff(Request $request)
    {
        $data = $request->validate([
            'barber_id' => 'required|exists:barbers,id',
            'date'      => 'required|date|after_or_equal:today',
            'reason'    => 'nullable|string|max:100',
        ]);

        $dayOff = BarberDayOff::firstOrCreate(
            ['barber_id' => $data['barber_id'], 'date' => $data['date']],
            ['reason' => $data['reason'] ?? null]
        );

        // Warn the owner if customers already booked this barber on that day
        $existing = Booking::where('barber_id', $data['barber_id'])
            ->where('booking_date', $data['date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $name = $dayOff->barber->name;
        $msg  = "Day off added for {$name} on " . $dayOff->date->format('d M Y') . '.';
        if ($existing > 0) {
            $msg .= " Note: {$name} still has {$existing} booking(s) that day - please contact those customers or move them.";
        }

        return back()->with('success', $msg);
    }

    /** Remove a day off. */
    public function destroyDayOff(BarberDayOff $dayOff)
    {
        $dayOff->delete();

        return back()->with('success', 'Day off removed.');
    }
}
