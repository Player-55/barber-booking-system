<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Barber;
use App\Support\SlotFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    /**
     * All time slots of a day for a service, marked available / full (called by JavaScript).
     * GET /booking/slots?service_id=2&date=2026-11-05
     */
    public function slots(Request $request)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id,is_active,1',
            'date'       => 'required|date|after_or_equal:today',
        ]);

        $finder = new SlotFinder();
        $slots  = $finder->daySlots(Service::findOrFail($data['service_id']), $data['date']);

        return response()->json(['slots' => $slots, 'message' => $finder->reason]);
    }

    /**
     * Save a booking. The customer picks service + date + time;
     * a FREE barber is chosen at random automatically.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|regex:/^[0-9]{9,11}$/',
            'service_id'     => 'required|exists:services,id,is_active,1',
            'booking_date'   => 'required|date|after_or_equal:today|before_or_equal:' . now()->addDays(30)->toDateString(),
            'booking_time'   => 'required|date_format:H:i',
        ], [
            'customer_phone.regex' => 'Phone number must be 9 to 11 digits (numbers only).',
            'booking_time.required' => 'Please choose a time slot.',
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $booking = DB::transaction(function () use ($validated, $service) {
            // 1. Lock the barbers so two customers can't grab the same last slot at the same moment
            Barber::where('is_active', true)->orderBy('id')->lockForUpdate()->get();

            // 2. Which barbers are free for this whole appointment?
            $freeIds = (new SlotFinder())->freeBarberIds($service, $validated['booking_date'], $validated['booking_time']);

            if (empty($freeIds)) {
                throw ValidationException::withMessages([
                    'booking_time' => 'Sorry, that time was just taken. Please pick another slot.',
                ]);
            }

            // 3. Pick one of the free barbers at random
            $barberId = $freeIds[array_rand($freeIds)];

            $start = $validated['booking_time'];
            $end   = Carbon::createFromFormat('H:i', $start)->addMinutes($service->duration_minutes)->format('H:i');

            return Booking::create([
                'booking_code'   => 'PZ-' . strtoupper(substr(uniqid(), -6)),
                'customer_name'  => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'service_id'     => $service->id,
                'barber_id'      => $barberId,
                'booking_date'   => $validated['booking_date'],
                'booking_time'   => $start,
                'end_time'       => $end,
                'total_price'    => $service->price,
                'deposit_amount' => config('app.booking_deposit'),
                'status'         => 'pending',
                'payment_status' => 'unpaid',
            ]);
        });

        // Fast string manipulation for WhatsApp redirect
        $serviceName = Service::where('id', $booking->service_id)->value('name');
        $barberName  = Barber::where('id', $booking->barber_id)->value('name');
        $formattedDate = Carbon::parse($booking->booking_date)->format('d M Y');
        $formattedTime = Carbon::parse($booking->booking_time)->format('h:i A');

        $message = "💈 *" . strtoupper(config('app.name')) . " - BOOKING RECEIPT* 💈\n\n"
            . "Booking Code: *{$booking->booking_code}*\n"
            . "Name: *{$booking->customer_name}*\n"
            . "Service: *{$serviceName}*\n"
            . "Barber: *{$barberName}*\n"
            . "Date: *{$formattedDate}*\n"
            . "Time: *{$formattedTime}*";

        $deposit = config('app.booking_deposit');
        if ($deposit > 0) {
            $message .= "\n\nDeposit Required: RM " . number_format($deposit, 2);
        }

        // Send the receipt to the shop's WhatsApp if a number is set in .env ...
        $shopWhatsapp = preg_replace('/\D/', '', (string) config('app.shop_whatsapp'));
        if ($shopWhatsapp !== '') {
            return redirect()->away("https://wa.me/{$shopWhatsapp}?text=" . urlencode($message));
        }

        // ... otherwise show the receipt on the booking page
        return redirect()->route('home')->with('booking', [
            'code'    => $booking->booking_code,
            'name'    => $booking->customer_name,
            'service' => $serviceName,
            'barber'  => $barberName,
            'date'    => $formattedDate,
            'time'    => $formattedTime,
        ]);
    }
}