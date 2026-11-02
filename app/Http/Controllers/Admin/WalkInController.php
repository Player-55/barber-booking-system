<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Support\SlotFinder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owner adds bookings for walk-in or phone customers.
 * Uses the same rules as online booking, so double-booking is still impossible.
 */
class WalkInController extends Controller
{
    /** The "Add Walk-in" form. */
    public function create(Request $request)
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $barbers  = Barber::where('is_active', true)->orderBy('name')->get();

        $date = $request->query('date');
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || Carbon::parse($date)->lt(today())) {
            $date = today()->toDateString();
        }

        return view('admin.walkin', compact('services', 'barbers', 'date'));
    }

    /**
     * Time slots for the form (called by JavaScript).
     * GET /admin/walk-in/slots?service_id=1&date=2026-11-05&barber_id=2 (barber optional)
     */
    public function slots(Request $request)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'date'       => 'required|date|after_or_equal:today',
            'barber_id'  => 'nullable|exists:barbers,id',
        ]);

        $finder = new SlotFinder();
        $only   = ! empty($data['barber_id']) ? [(int) $data['barber_id']] : null;
        $slots  = $finder->daySlots(Service::findOrFail($data['service_id']), $data['date'], $only);

        return response()->json(['slots' => $slots, 'message' => $finder->reason]);
    }

    /** Save the walk-in booking. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => ['nullable', 'regex:/^[0-9]{9,11}$/'],
            'service_id'     => 'required|exists:services,id,is_active,1',
            'barber_id'      => 'nullable|exists:barbers,id,is_active,1',
            'booking_date'   => 'required|date|after_or_equal:today',
            'booking_time'   => 'required|date_format:H:i',
            'status'         => 'required|in:confirmed,completed',
        ], [
            'customer_phone.regex' => 'Phone number must be 9 to 11 digits (numbers only).',
            'booking_time.required' => 'Please choose a time.',
        ]);

        $service = Service::findOrFail($data['service_id']);

        $booking = DB::transaction(function () use ($data, $service) {
            // Lock barbers so an online customer can't take the same slot at the same moment
            Barber::where('is_active', true)->orderBy('id')->lockForUpdate()->get();

            $only    = ! empty($data['barber_id']) ? [(int) $data['barber_id']] : null;
            $freeIds = (new SlotFinder())->freeBarberIds($service, $data['booking_date'], $data['booking_time'], $only, true);

            if (empty($freeIds)) {
                throw ValidationException::withMessages([
                    'booking_time' => $only
                        ? 'That barber is not free at this time (busy, on break, day off, or outside opening hours).'
                        : 'No barber is free at this time (busy, on break, or outside opening hours).',
                ]);
            }

            $start = $data['booking_time'];

            return Booking::create([
                'booking_code'   => 'WI-' . strtoupper(substr(uniqid(), -6)),
                'customer_name'  => $data['customer_name'] ?: 'Walk-in Customer',
                'customer_phone' => $data['customer_phone'] ?? '',
                'service_id'     => $service->id,
                'barber_id'      => $freeIds[array_rand($freeIds)],
                'booking_date'   => $data['booking_date'],
                'booking_time'   => $start,
                'end_time'       => Carbon::createFromFormat('H:i', $start)->addMinutes($service->duration_minutes)->format('H:i'),
                'total_price'    => $service->price,
                'deposit_amount' => 0,
                'status'         => $data['status'],
                'payment_status' => 'unpaid',
            ]);
        });

        $booking->load('barber:id,name');

        return redirect()
            ->route('admin.dashboard', ['date' => $data['booking_date']])
            ->with('success', "Walk-in added: {$booking->customer_name} with {$booking->barber->name} at "
                . Carbon::createFromFormat('H:i', $booking->booking_time)->format('h:i A') . '.');
    }
}
