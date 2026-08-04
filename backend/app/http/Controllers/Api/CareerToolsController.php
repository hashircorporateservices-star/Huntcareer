<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CareerToolsService;
use Illuminate\Http\Request;

class CareerToolsController extends Controller
{
    public function __construct(protected CareerToolsService $service) {}

    public function salaryEstimate(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:120',
            'country' => 'required|string|size:2',
            'years'   => 'nullable|integer|min:0|max:60',
        ]);

        return response()->json(
            $this->service->salaryEstimate($data['title'], strtoupper($data['country']), $data['years'] ?? null)
        );
    }

    public function followUp(Request $request)
    {
        $data = $request->validate([
            'type'    => 'required|in:post_application,post_interview,thank_you',
            'context' => 'array',
        ]);

        return response()->json(['body' => $this->service->followUp($data['type'], $data['context'] ?? [])]);
    }
}
