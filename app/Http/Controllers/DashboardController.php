<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StatusLog;
use Carbon\Carbon;


class DashboardController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function counts()
    {
        $partnerCount = Partner::count();

        $productCounts = Product::select('category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->get();

        $result = [
            'partners' => $partnerCount,
            'products' => $productCounts->mapWithKeys(function ($item) {
                return [$item->category => $item->total];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }


    public function status()
    {
        // Ambil semua log, urut terbaru
        $logs = StatusLog::latest()->get();

        // Format data untuk API
        $data = $logs->map(function ($log) {
            // Pilih nama / title yang sudah disimpan
            $name = $log->product_title ?? $log->partner_name ?? 'Item';

            // Action
            $action = $log->action;

            // Waktu human-readable
            $timeAgo = Carbon::parse($log->created_at)->diffForHumans([
                'short' => false,    // full format: 5 hours ago
                'parts' => 3,        // max parts
                'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
            ]);

            return [
                'text' => "$name was $action",
                'time_ago' => $timeAgo,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

}
