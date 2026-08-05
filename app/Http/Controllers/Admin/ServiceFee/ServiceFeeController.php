<?php

namespace App\Http\Controllers\Admin\ServiceFee;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceFeeController extends Controller
{
    public function index()
    {
        $fees = ServiceFee::whereIn('key', ServiceFee::KEYS)->get()->keyBy('key');

        return view('dashboard.admin.service-fees.index', compact('fees'));
    }

    /**
     * Edits and saves every category in a single request — replaces the
     * previous per-row update(string $key) with one submit for the whole
     * page. fees[reservation][amount], fees[reservation][is_active], etc.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'fees' => ['required', 'array'],
            'fees.*.amount' => ['required', 'numeric', 'min:0'],
            'fees.*.is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['fees'] as $key => $values) {
                if (! in_array($key, ServiceFee::KEYS)) {
                    continue;
                }

                $ser = ServiceFee::where('key', $key)->update([
                    'amount' => $values['amount'],
                    'is_active' => ! empty($values['is_active']),
                ]);

            }
        });

        return redirect()->route('admin.service-fees.index')
            ->with('success', __('lang.service_fee_updated'));
    }
}
