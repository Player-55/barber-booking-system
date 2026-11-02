<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Booking;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    /** List all barbers (active and hidden). */
    public function index()
    {
        $barbers = Barber::orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.barbers', compact('barbers'));
    }

    /** Add a new barber. */
    public function store(Request $request)
    {
        Barber::create($this->validated($request));

        return back()->with('success', 'Barber added.');
    }

    /** Save changes to a barber. */
    public function update(Request $request, Barber $barber)
    {
        $barber->update($this->validated($request));

        return back()->with('success', "{$barber->name} updated.");
    }

    /** Delete a barber - only if he has never been booked (otherwise hide him). */
    public function destroy(Barber $barber)
    {
        if (Booking::where('barber_id', $barber->id)->exists()) {
            return back()->with('error', "{$barber->name} already has bookings, so he can't be deleted. Untick \"Taking bookings\" to hide him instead.");
        }

        $barber->delete();

        return back()->with('success', 'Barber deleted.');
    }

    /** Rules shared by add + edit. */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'specialty' => 'nullable|string|max:50',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\- ]+$/'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
