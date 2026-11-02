<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Show the owner login page.
     */
    public function showLogin()
    {
        return view('admin.login');
    }

    /**
     * Check the email + password and log the owner in.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            // New session ID after login (protects against session hijacking)
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back!');
        }

        return back()
            ->withErrors(['email' => 'Wrong email or password.'])
            ->onlyInput('email');
    }

    /**
     * Log the owner out and clear the session.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }

    /**
     * Admin dashboard: clock, totals, calendar, and the bookings of ONE chosen day.
     * URL examples: /admin/dashboard?date=2026-11-05   /admin/dashboard?month=2026-12
     */
    public function index(Request $request)
    {
        // Which day to show (default: today) and which month the calendar shows
        $selected = $this->parseDate($request->query('date')) ?? today();
        $month    = $this->parseMonth($request->query('month')) ?? $selected->copy()->startOfMonth();
        $status   = in_array($request->query('status'), ['pending', 'confirmed', 'completed', 'cancelled'])
            ? $request->query('status')
            : null;

        // Overall totals (left panel)
        $totalBookings   = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $totalRevenue    = Booking::where('status', 'completed')->sum('total_price');

        // All bookings of the chosen day, earliest time first
        $dayBookings = Booking::with(['service:id,name,price', 'barber:id,name'])
            ->where('booking_date', $selected->toDateString())
            ->orderBy('booking_time')
            ->orderBy('id')
            ->get();

        // Queue number for the day (#1, #2, ...) - stays the same even when filtering by status
        foreach ($dayBookings as $i => $booking) {
            $booking->queue_no = $i + 1;
        }

        $bookings = $status ? $dayBookings->where('status', $status)->values() : $dayBookings;

        // How many active (not cancelled) bookings each day of the calendar month has
        $counts = Booking::whereBetween('booking_date', [
                $month->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->where('status', '!=', 'cancelled')
            ->get(['booking_date'])
            ->groupBy(fn ($b) => substr((string) $b->booking_date, 0, 10))
            ->map->count();

        return view('admin.dashboard', compact(
            'bookings', 'dayBookings', 'totalBookings', 'pendingBookings', 'totalRevenue',
            'selected', 'month', 'counts', 'status'
        ));
    }

    /** "2026-11-05" -> Carbon date, or null if missing/invalid. */
    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    /** "2026-11" -> first day of that month, or null if missing/invalid. */
    private function parseMonth(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^(\d{4})-(\d{2})$/', $value, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
    }

    /**
     * Change a booking's status (pending / confirmed / completed / cancelled).
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $booking->update(['status' => $request->status]);

        return back()->with('success', "Booking {$booking->booking_code} status updated to {$request->status}!");
    }
}
