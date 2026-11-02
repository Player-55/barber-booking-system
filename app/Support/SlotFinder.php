<?php

namespace App\Support;

use App\Models\Barber;
use App\Models\BarberDayOff;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Service;
use Carbon\Carbon;

/**
 * Works out which start times are free for a barber + service on a date.
 * A time is free when the whole appointment (start -> start + duration):
 *   - is inside opening hours, and the shop is open that day
 *   - does not overlap the break time
 *   - is not on the barber's day off
 *   - does not overlap another pending/confirmed booking of that barber
 *   - is not already in the past (for today)
 */
class SlotFinder
{
    /** Gap between offered start times, in minutes. */
    public const STEP = 30;

    /** Why there are no slots (shown to the customer), or null. */
    public ?string $reason = null;

    /** @return array<int, array{value: string, label: string}> */
    public function availableSlots(Barber $barber, Service $service, string $date): array
    {
        $this->reason = null;
        $day = Carbon::parse($date);

        $hours = OpeningHour::where('day_of_week', $day->dayOfWeek)->first();
        if (! $hours || $hours->is_closed) {
            $this->reason = 'The shop is closed on ' . $day->format('l') . 's.';
            return [];
        }

        if (BarberDayOff::where('barber_id', $barber->id)->where('date', $day->toDateString())->exists()) {
            $this->reason = "{$barber->name} is not working on this day. Please choose another barber or date.";
            return [];
        }

        $taken = Booking::where('barber_id', $barber->id)
            ->where('booking_date', $day->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['booking_time', 'end_time']);

        $duration   = (int) $service->duration_minutes;
        $open       = $this->toMinutes($hours->open_time);
        $close      = $this->toMinutes($hours->close_time);
        $breakStart = $hours->break_start ? $this->toMinutes($hours->break_start) : null;
        $breakEnd   = $hours->break_end ? $this->toMinutes($hours->break_end) : null;
        $nowMinutes = $day->isToday() ? now()->hour * 60 + now()->minute : -1;

        $slots = [];
        for ($start = $open; $start + $duration <= $close; $start += self::STEP) {
            $end = $start + $duration;

            if ($start <= $nowMinutes) {
                continue; // already passed today
            }
            if ($breakStart !== null && $breakEnd !== null && $start < $breakEnd && $end > $breakStart) {
                continue; // overlaps break
            }
            foreach ($taken as $booking) {
                if ($start < $this->toMinutes($booking->end_time) && $end > $this->toMinutes($booking->booking_time)) {
                    continue 2; // overlaps an existing booking
                }
            }

            $value   = $this->toTime($start);
            $slots[] = ['value' => $value, 'label' => OpeningHour::niceTime($value)];
        }

        if (empty($slots)) {
            $this->reason = 'No free times left on this day. Please choose another date or barber.';
        }

        return $slots;
    }

    /**
     * ALL start times of a day for a service, each marked available or full.
     * A time is available when at least ONE active barber is free for the whole appointment.
     *
     * @return array<int, array{value: string, label: string, available: bool}>
     */
    public function daySlots(Service $service, string $date, ?array $onlyBarberIds = null): array
    {
        $this->reason = null;
        $day = Carbon::parse($date);

        $hours = OpeningHour::where('day_of_week', $day->dayOfWeek)->first();
        if (! $hours || $hours->is_closed) {
            $this->reason = 'The shop is closed on ' . $day->format('l') . 's.';
            return [];
        }

        $barberIds = $this->workingBarberIds($day);
        if ($onlyBarberIds !== null) {
            $barberIds = array_values(array_intersect($barberIds, $onlyBarberIds));
        }
        if (empty($barberIds)) {
            $this->reason = $onlyBarberIds !== null
                ? 'This barber is not working on this day.'
                : 'No barbers are working on this day. Please choose another date.';
            return [];
        }

        $takenByBarber = $this->takenByBarber($barberIds, $day);
        $duration      = (int) $service->duration_minutes;

        $slots = [];
        foreach ($this->candidateStarts($hours, $duration, $day) as $start) {
            $end  = $start + $duration;
            $free = false;
            foreach ($barberIds as $id) {
                if ($this->isFree($takenByBarber[$id] ?? [], $start, $end)) {
                    $free = true;
                    break;
                }
            }
            $value   = $this->toTime($start);
            $slots[] = ['value' => $value, 'label' => OpeningHour::niceTime($value), 'available' => $free];
        }

        if (! collect($slots)->contains('available', true)) {
            $this->reason = empty($slots)
                ? 'No more times left today. Please choose another date.'
                : 'Fully booked on this day. Please choose another date.';
        }

        return $slots;
    }

    /**
     * IDs of the barbers who are free for this service at this exact start time.
     *
     * @return array<int, int>
     */
    public function freeBarberIds(Service $service, string $date, string $startTime, ?array $onlyBarberIds = null, bool $anyMinute = false): array
    {
        $day   = Carbon::parse($date);
        $hours = OpeningHour::where('day_of_week', $day->dayOfWeek)->first();
        if (! $hours || $hours->is_closed) {
            return [];
        }

        $duration = (int) $service->duration_minutes;
        $start    = $this->toMinutes($startTime);

        $fits = $anyMinute
            ? $this->fitsAnyMinute($hours, $duration, $day, $start)          // walk-ins: any minute, e.g. "start now"
            : in_array($start, $this->candidateStarts($hours, $duration, $day), true); // online: 30-min slots only
        if (! $fits) {
            return []; // outside hours, in the break, or already passed
        }

        $barberIds = $this->workingBarberIds($day);
        if ($onlyBarberIds !== null) {
            $barberIds = array_values(array_intersect($barberIds, $onlyBarberIds));
        }
        $takenByBarber = $this->takenByBarber($barberIds, $day);

        return array_values(array_filter(
            $barberIds,
            fn ($id) => $this->isFree($takenByBarber[$id] ?? [], $start, $start + $duration)
        ));
    }

    /** Start times (in minutes) inside opening hours, outside the break, not in the past. */
    private function candidateStarts(OpeningHour $hours, int $duration, Carbon $day): array
    {
        $open       = $this->toMinutes($hours->open_time);
        $close      = $this->toMinutes($hours->close_time);
        $breakStart = $hours->break_start ? $this->toMinutes($hours->break_start) : null;
        $breakEnd   = $hours->break_end ? $this->toMinutes($hours->break_end) : null;
        $nowMinutes = $day->isToday() ? now()->hour * 60 + now()->minute : -1;

        $starts = [];
        for ($start = $open; $start + $duration <= $close; $start += self::STEP) {
            $end = $start + $duration;
            if ($start <= $nowMinutes) {
                continue;
            }
            if ($breakStart !== null && $breakEnd !== null && $start < $breakEnd && $end > $breakStart) {
                continue;
            }
            $starts[] = $start;
        }

        return $starts;
    }

    /**
     * Walk-in check: inside opening hours, not in the break, and not more than
     * 15 minutes in the past (so the owner can log a customer who just sat down).
     */
    private function fitsAnyMinute(OpeningHour $hours, int $duration, Carbon $day, int $start): bool
    {
        $open  = $this->toMinutes($hours->open_time);
        $close = $this->toMinutes($hours->close_time);
        $end   = $start + $duration;

        if ($start < $open || $end > $close) {
            return false;
        }
        if ($hours->break_start && $hours->break_end
            && $start < $this->toMinutes($hours->break_end) && $end > $this->toMinutes($hours->break_start)) {
            return false;
        }
        if ($day->isToday() && $start < now()->hour * 60 + now()->minute - 15) {
            return false;
        }
        if ($day->lt(today())) {
            return false;
        }

        return true;
    }

    /** Active barbers who are not on a day off. */
    private function workingBarberIds(Carbon $day): array
    {
        $off = BarberDayOff::where('date', $day->toDateString())->pluck('barber_id')->all();

        return Barber::where('is_active', true)->whereNotIn('id', $off)->orderBy('id')->pluck('id')->all();
    }

    /** [barber_id => [[startMin, endMin], ...]] for pending/confirmed bookings that day. */
    private function takenByBarber(array $barberIds, Carbon $day): array
    {
        $result = [];
        Booking::whereIn('barber_id', $barberIds)
            ->where('booking_date', $day->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['barber_id', 'booking_time', 'end_time'])
            ->each(function ($b) use (&$result) {
                $result[$b->barber_id][] = [$this->toMinutes($b->booking_time), $this->toMinutes($b->end_time)];
            });

        return $result;
    }

    private function isFree(array $taken, int $start, int $end): bool
    {
        foreach ($taken as [$s, $e]) {
            if ($start < $e && $end > $s) {
                return false;
            }
        }

        return true;
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));
        return $h * 60 + $m;
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
