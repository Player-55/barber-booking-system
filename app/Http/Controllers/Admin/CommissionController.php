<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    /** Current commission rate in % (saved in the settings table, default from .env). */
    public static function rate(): float
    {
        $saved = Setting::where('key', 'commission_rate')->value('value');

        return $saved !== null ? (float) $saved : (float) config('app.commission_rate');
    }

    /**
     * Commission per barber for a date range.
     * Commission is paid on COMPLETED bookings only.
     */
    public function index(Request $request)
    {
        $from = $this->parseDate($request->query('from')) ?? today()->startOfMonth();
        $to   = $this->parseDate($request->query('to')) ?? today()->endOfMonth()->startOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $rate = self::rate();

        $bookings = Booking::whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->get(['barber_id', 'status', 'total_price']);

        $rows = Barber::orderBy('name')->get()->map(function ($barber) use ($bookings, $rate) {
            $mine      = $bookings->where('barber_id', $barber->id);
            $completed = $mine->where('status', 'completed');
            $upcoming  = $mine->whereIn('status', ['pending', 'confirmed']);
            $sales     = (float) $completed->sum('total_price');
            $commission = round($sales * $rate / 100, 2);

            return (object) [
                'name'            => $barber->name,
                'is_active'       => $barber->is_active,
                'completed'       => $completed->count(),
                'sales'           => $sales,
                'commission'      => $commission,
                'shop'            => $sales - $commission,
                'upcoming'        => $upcoming->count(),
                'upcoming_value'  => (float) $upcoming->sum('total_price'),
            ];
        })
        // hide hidden barbers that have nothing in this period
        ->filter(fn ($r) => $r->is_active || $r->completed || $r->upcoming)
        ->values();

        $totals = (object) [
            'completed'      => $rows->sum('completed'),
            'sales'          => $rows->sum('sales'),
            'commission'     => $rows->sum('commission'),
            'shop'           => $rows->sum('shop'),
            'upcoming'       => $rows->sum('upcoming'),
            'upcoming_value' => $rows->sum('upcoming_value'),
        ];

        return view('admin.commission', compact('rows', 'totals', 'rate', 'from', 'to'));
    }

    /** Change the commission rate. */
    public function updateRate(Request $request)
    {
        $data = $request->validate(['rate' => 'required|numeric|min:0|max:100']);

        Setting::updateOrCreate(['key' => 'commission_rate'], ['value' => (string) $data['rate']]);

        return back()->with('success', 'Commission rate updated to ' . rtrim(rtrim(number_format($data['rate'], 2), '0'), '.') . '%.');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
