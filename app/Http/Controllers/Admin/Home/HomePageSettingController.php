<?php

namespace App\Http\Controllers\Admin\Home;

use App\Http\Controllers\Controller;
use App\Models\HomeSetting;
use App\Models\HomeSlide;
use App\Models\Unite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HomePageSettingController extends Controller
{
    public function edit()
    {
        $settings = HomeSetting::current();
        $slides = HomeSlide::orderBy('sort_order')->orderBy('id')->get();
        $unites = Unite::where('status', 'active')->orderBy('name')->get(['id', 'name', 'type', 'location_name']);

        return view('dashboard.admin.homepage.edit', compact('settings', 'slides', 'unites'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'logo_light' => ['nullable', 'image', 'max:4096'],
            'app_image' => ['nullable', 'image', 'max:8192'],
            'why_us_image' => ['nullable', 'image', 'max:8192'],
            // Arabic text
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'search_title' => ['nullable', 'string', 'max:255'],
            'featured_title' => ['nullable', 'string', 'max:255'],
            'featured_subtitle' => ['nullable', 'string', 'max:1000'],
            'app_title' => ['nullable', 'string', 'max:255'],
            'app_text' => ['nullable', 'string', 'max:1000'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            // English text
            'hero_badge_en' => ['nullable', 'string', 'max:255'],
            'hero_title_en' => ['nullable', 'string', 'max:255'],
            'hero_subtitle_en' => ['nullable', 'string', 'max:1000'],
            'search_title_en' => ['nullable', 'string', 'max:255'],
            'featured_title_en' => ['nullable', 'string', 'max:255'],
            'featured_subtitle_en' => ['nullable', 'string', 'max:1000'],
            'app_title_en' => ['nullable', 'string', 'max:255'],
            'app_text_en' => ['nullable', 'string', 'max:1000'],
            'footer_text_en' => ['nullable', 'string', 'max:1000'],
            // Other
            'featured_unite_ids' => ['nullable', 'array'],
            'featured_unite_ids.*' => ['integer', 'exists:unites,id'],
            'featured_limit' => ['required', 'integer', 'min:4', 'max:24'],
            'google_play_url' => ['nullable', 'url', 'max:1000'],
            'apple_store_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $settings = HomeSetting::current();

        foreach (['show_search', 'show_categories', 'show_featured', 'show_stats', 'show_why_us', 'show_app_section'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        foreach ([
            'logo' => 'logo_path',
            'logo_light' => 'logo_light_path',
            'app_image' => 'app_image_path',
            'why_us_image' => 'why_us_image_path',
        ] as $input => $column) {
            if ($request->boolean('remove_'.$input)) {
                $this->deletePublicFile($settings->{$column});
                $data[$column] = null;
            }
            if ($request->hasFile($input)) {
                $this->deletePublicFile($settings->{$column});
                $data[$column] = $this->storeImage($request->file($input), 'homepage/settings');
            }
        }

        unset($data['logo'], $data['logo_light'], $data['app_image'], $data['why_us_image']);
        $settings->update($data);

        return back()->with('success', 'تم حفظ إعدادات الصفحة الرئيسية بنجاح.');
    }

    public function storeSlide(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'image' => ['required', 'image', 'max:8192'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        HomeSlide::create([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'image' => $this->storeImage($request->file('image'), 'homepage/slides'),
            'button_text' => $data['button_text'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'تمت إضافة السلايد بنجاح.');
    }

    public function updateSlide(Request $request, HomeSlide $slide)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:8192'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicFile($slide->image);
            $data['image'] = $this->storeImage($request->file('image'), 'homepage/slides');
        }

        $data['is_active'] = $request->boolean('is_active');
        $slide->update($data);

        return back()->with('success', 'تم تحديث السلايد بنجاح.');
    }

    public function destroySlide(HomeSlide $slide)
    {
        $this->deletePublicFile($slide->image);
        $slide->delete();

        return back()->with('success', 'تم حذف السلايد.');
    }

    private function storeImage($file, string $directory): string
    {
        $dir = public_path($directory);
        File::ensureDirectoryExists($dir);
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = now()->format('YmdHis').'_'.bin2hex(random_bytes(5)).'.'.$extension;
        $file->move($dir, $name);

        return trim($directory, '/').'/'.$name;
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $path = public_path($relativePath);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
