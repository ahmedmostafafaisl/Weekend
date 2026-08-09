<?php

namespace App\Http\Controllers\Admin\Viewing;

use App\Http\Controllers\Controller;
use App\Models\UniteViewing;
use Illuminate\Http\Request;

/**
 * Admin-facing viewing-appointment management — lists every booked
 * appointment across all venues, and lets an admin view/add/remove the
 * registered users attached to a single appointment ("all people
 * associated with the appointment can be viewed and managed from the
 * control panel").
 */
class UniteViewingController extends Controller
{
    public function index(Request $request)
    {
        $viewings = UniteViewing::with(['unite', 'user', 'viewingTime', 'attendees'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('unite_id'), fn ($q, $uniteId) => $q->where('unite_id', $uniteId))
            ->when($request->query('date_from'), fn ($q, $date) => $q->where('viewing_date', '>=', $date))
            ->when($request->query('date_to'), fn ($q, $date) => $q->where('viewing_date', '<=', $date))
            ->latest('viewing_date')
            ->paginate(20)
            ->withQueryString();

        // For the venue filter dropdown — every venue that has at least
        // one viewing appointment, not every venue in the system, so an
        // admin isn't scrolling through venues with nothing to show.
        $unitesWithViewings = \App\Models\Unite::whereHas('viewings')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard.admin.viewings.index', compact('viewings', 'unitesWithViewings'));
    }

    public function show(int $id)
    {
        $viewing = UniteViewing::with(['unite', 'user', 'viewingTime', 'attendees', 'payment'])
            ->findOrFail($id);

        return view('dashboard.admin.viewings.show', compact('viewing'));
    }

    /**
     * Adds a registered user to an existing appointment — e.g. the
     * booker calls in and asks to add a 4th person after the fact.
     * Silently no-ops if the user is already attached, rather than
     * erroring over a harmless duplicate attempt.
     */
    public function addAttendee(Request $request, int $id)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $viewing = UniteViewing::findOrFail($id);
        $viewing->attendees()->syncWithoutDetaching([$data['user_id']]);

        return redirect()->route('admin.viewings.show', $viewing->id)
            ->with('success', __('lang.attendee_added_successfully'));
    }

    /**
     * Removes a registered user from an appointment. The primary booker
     * (unite_viewings.user_id) can't be removed this way — doing so would
     * leave the appointment without the person who actually booked and
     * (possibly) paid the deposit for it; cancelling the whole appointment
     * is the correct action for that case instead.
     */
    public function removeAttendee(int $id, int $userId)
    {
        $viewing = UniteViewing::findOrFail($id);

        if ($viewing->user_id === $userId) {
            return redirect()->route('admin.viewings.show', $viewing->id)
                ->with('error', __('lang.cannot_remove_primary_booker'));
        }

        $viewing->attendees()->detach($userId);

        return redirect()->route('admin.viewings.show', $viewing->id)
            ->with('success', __('lang.attendee_removed_successfully'));
    }
}
