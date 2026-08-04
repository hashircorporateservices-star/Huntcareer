<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HiringManagerContact;
use App\Services\PlanService;
use Illuminate\Http\Request;

/**
 * Hiring Manager Contacts (feature #4). Details are hidden until revealed;
 * revealing spends 1 credit (admins reveal free).
 */
class ContactController extends Controller
{
    public function index(Request $request, PlanService $plans)
    {
        $contacts = HiringManagerContact::with('company:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'credits'  => $plans->balance($request->user()),
            'contacts' => $contacts,   // toArray() masks email/linkedin until revealed
        ]);
    }

    public function reveal(Request $request, HiringManagerContact $contact, PlanService $plans)
    {
        abort_unless($contact->user_id === $request->user()->id, 403);

        try {
            $revealed = $plans->revealContact($request->user(), $contact);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 402); // out of credits
        }

        return response()->json([
            'contact' => $revealed,
            'credits' => $plans->balance($request->user()),
        ]);
    }
}
