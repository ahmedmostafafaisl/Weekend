<?php

namespace App\Http\Controllers\Admin\ServiceFee;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use Illuminate\Http\Request;

class ServiceFeeController extends Controller
{
    public function index()
    {
        $fees = ServiceFee::whereIn('key', ServiceFee::KEYS)->get()->keyBy('key');

        return view('dashboard.admin.service-fees.index', compact('fees'));
    }

    public function update(Request $request, string $key)
    {
        if (! in_array($key, ServiceFee::KEYS)) {
            abort(404);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fee = ServiceFee::where('key', $key)->firstOrFail();
        $fee->update([
            'amount' => $data['amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.service-fees.index')
            ->with('success', __('lang.service_fee_updated'));
    }
}
