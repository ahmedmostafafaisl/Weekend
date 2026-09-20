<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HomeSetting;
use App\Models\HomeSlide;
use App\Models\Unite;
use App\Models\UniteReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $settings = Schema::hasTable('home_settings') ? HomeSetting::current() : new HomeSetting([
            'site_name' => 'Weekend',
            // Arabic
            'hero_badge' => 'ويكند — كل لحظة تستحقها',
            'hero_title' => 'أماكن تصنع لحظات لا تُنسى',
            'hero_subtitle' => 'اكتشف أفضل الملاعب والقاعات والاستراحات والمخيمات، وأكمل الحجز من تطبيق ويكند.',
            'search_title' => 'ابحث عن مكانك المثالي',
            'featured_title' => 'أماكن مميزة',
            'featured_subtitle' => 'اختيارات مميزة تناسب مناسبتك القادمة',
            'app_title' => 'ويكند معك أينما كنت',
            'app_text' => 'اكتشف أماكن جديدة، قارن الخيارات، وأكمل الحجز من تطبيق ويكند بخطوات بسيطة.',
            'footer_text' => 'منصة سعودية تجمع أفضل أماكن التجارب والمناسبات في مكان واحد.',
            // English
            'hero_badge_en' => 'Weekend — Every moment counts',
            'hero_title_en' => 'Places that make unforgettable moments',
            'hero_subtitle_en' => 'Discover the best stadiums, halls, lounges and camps, and complete your booking on the Weekend app.',
            'search_title_en' => 'Find your perfect venue',
            'featured_title_en' => 'Featured Venues',
            'featured_subtitle_en' => 'Handpicked selections for your next occasion',
            'app_title_en' => 'Weekend, wherever you are',
            'app_text_en' => 'Discover new places, compare options, and book in minutes on the Weekend app.',
            'footer_text_en' => 'A Saudi platform bringing together the best experience and event venues in one place.',
            // Flags
            'featured_limit' => 8,
            'show_search' => true, 'show_categories' => true, 'show_featured' => true,
            'show_stats' => true, 'show_why_us' => true, 'show_app_section' => true,
        ]);

        $slides = Schema::hasTable('home_slides')
            ? HomeSlide::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $query = Unite::query()
            ->with(['images', 'prices', 'ratings', 'features', 'newFeatures', 'services', 'detail', 'offers', 'department'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->where('status', 'active');

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('location_name', 'like', "%{$term}%")
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', "%{$term}%"));
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('city')) {
            $query->where(function ($q) use ($request) {
                $q->where('city', 'like', '%'.$request->city.'%')
                    ->orWhere('location_name', 'like', '%'.$request->city.'%');
            });
        }
        if ($request->filled('date')) {
            $query->whereDoesntHave('reservations', function ($r) use ($request) {
                $r->whereIn('status', ['pending', 'confirmed'])
                    ->whereDate('reservation_date', '<=', $request->date)
                    ->whereRaw('COALESCE(end_date, reservation_date) >= ?', [$request->date]);
            });
        }

        $selected = collect($settings->featured_unite_ids ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        if (! $request->hasAny(['q', 'type', 'city', 'date']) && $selected->isNotEmpty()) {
            $query->whereIn('id', $selected);
        }

        $unites = $query->latest()->take(max(4, min((int) ($settings->featured_limit ?: 8), 24)))->get();
        $departmentsCount = Department::where('status', 'active')->count();
        $unitesCount = Unite::where('status', 'active')->count();
        $reservationsCount = UniteReservation::count();
        $citiesCount = Unite::where('status', 'active')->whereNotNull('city')->distinct('city')->count('city');

        return view('home.index', compact('settings', 'slides', 'unites', 'departmentsCount', 'unitesCount', 'reservationsCount', 'citiesCount'));
    }
}
