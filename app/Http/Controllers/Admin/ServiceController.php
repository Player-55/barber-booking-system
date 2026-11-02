<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /** List all services (active and hidden). */
    public function index()
    {
        $services = Service::orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.services', compact('services'));
    }

    /** Add a new service. */
    public function store(Request $request)
    {
        Service::create($this->validated($request));

        return back()->with('success', 'Service added.');
    }

    /** Save changes to a service. */
    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request));

        return back()->with('success', "\"{$service->name}\" updated.");
    }

    /**
     * Delete a service - only if it has never been booked.
     * (Deleting a booked service would also delete its bookings and break the reports,
     * so for those we ask the owner to hide it instead.)
     */
    public function destroy(Service $service)
    {
        if (Booking::where('service_id', $service->id)->exists()) {
            return back()->with('error', "\"{$service->name}\" already has bookings, so it can't be deleted. Untick \"Show to customers\" to hide it instead.");
        }

        $service->delete();

        return back()->with('success', 'Service deleted.');
    }

    /** Rules shared by add + edit. */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'price'            => 'required|numeric|min:0|max:9999',
            'duration_minutes' => 'required|integer|min:5|max:240',
        ]);
        $data['is_active'] = $request->boolean('is_active'); // unticked checkbox = hidden

        return $data;
    }
}
