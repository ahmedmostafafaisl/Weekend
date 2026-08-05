<?php

use App\Http\Controllers\Admin\Ads\AdController;
use App\Http\Controllers\Admin\Ads\AdminAdController;
use App\Http\Controllers\Admin\Analytics\AnalyticsController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController;
use App\Http\Controllers\Admin\Dashboard\PermissionPageController;
use App\Http\Controllers\Admin\Dashboard\RolePageController;
use App\Http\Controllers\Admin\Dashboard\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InsurancePolicy\InsurancePolicyController;
use App\Http\Controllers\Admin\Packages\AdPackageController;
use App\Http\Controllers\Admin\Packages\PropertyPackageController;
use App\Http\Controllers\Admin\PromoCode\PromoCodeController;
use App\Http\Controllers\Admin\Reservation\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\Reviewer\ReviewerController;
use App\Http\Controllers\Admin\Service\ServiceController;
use App\Http\Controllers\Admin\Service\ServiceGroupController;
use App\Http\Controllers\Admin\StadiumType\StadiumTypeController;
use App\Http\Controllers\Admin\Subscription\SubscriptionController;
use App\Http\Controllers\Admin\Suggestion\SuggestionController;
use App\Http\Controllers\Admin\Unite\UniteFeatureController;
use App\Http\Controllers\Admin\Unite\UniteOfferController;
use App\Http\Controllers\Admin\Unite\UnitePackageController;
use App\Http\Controllers\Admin\Unite\UniteSlotController;
use App\Http\Controllers\AdminAuth\AuthenticatedSessionController;
use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\Dashboard\Admin\AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\Dashboard\ProviderDashboardController;
use App\Http\Controllers\Provider\Department\DepartmentController;
use App\Http\Controllers\Provider\Unite\UniteController;
use App\Http\Controllers\Reservation\UniteReservationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ── Language switcher ────────────────────────────────────────────────────────
Route::post('/locale/switch', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'en');
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth:admin', 'admin.guard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
require __DIR__.'/auth.php';
Route::prefix('admin')->name('admin.')->group(function () {

    // Provider statistics proxy — admin views any provider's stats
    Route::middleware(['admin'])->get('/api/provider-statistics/{provider}', function (\Illuminate\Http\Request $request, $provider) {
        $user = \App\Models\User::where('type', 'provider')->findOrFail($provider);
        $request->merge(['_provider_user' => $user]);

        return app(\App\Http\Controllers\Api\ProviderStatisticsController::class)($request);
    })->name('api.provider-statistics');

    Route::middleware(['admin'])->group(function () {

        // #------------------------------------------------------- ADMIN INDEX PAGE
        Route::get('/', AdminHomeController::class)->name('index');

        // #------------------------------------------------------- MARL ALL NOTIFICATIONS AS READ
        Route::get('/notification/markasread', function () {
            Auth::guard('admin')->user()->notifications->markAsRead();
        })->name('notifications.read');

        // #------------------------------------------------------- CLEAR ALL NOTIFICATIONS
        Route::get('/notification/clear', function () {
            Auth::guard('admin')->user()->notifications()->delete();
        })->name('notifications.clear');
        // servers

    });

    require __DIR__.'/adminAuth.php';

    // routes/web.php
    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {});
});

// Department Routes — handled by admin routes below (admin.departments.*)
// Bare web resource removed to prevent route name collisions

// Unite Routes for admin dashboard (auth:admin guard)
// Named unites.* — used by all views and sidebar links
Route::prefix('admin')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::patch('unites/{unite}/toggle-approval', [UniteController::class, 'toggleApproval'])
        ->name('unites.toggle-approval')
        ->middleware('permission:unites.update');
});

Route::prefix('admin')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::resource('unites', UniteController::class)->names([
        'index' => 'unites.index',
        'create' => 'unites.create',
        'store' => 'unites.store',
        'show' => 'unites.show',
        'edit' => 'unites.edit',
        'update' => 'unites.update',
        'destroy' => 'unites.destroy',
    ]);
});
// Route::resource('unite_offers', UniteOfferController::class);

// ads activate route (ads browsing served by API; only the activate action needed here)
Route::post('/ads/{id}/activate', [AdController::class, 'activate'])
    ->middleware('auth')
    ->name('ads.activate');

// Payment result pages — accessible by anyone (Geidea redirects here)
Route::get('/payment-complete', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment-failed', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'failed'])->name('payment.failed');
Route::get('/payment-cancelled', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'cancelled'])->name('payment.cancelled');

// Admin dashboard payment routes
Route::prefix('admin')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/payments', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/{id}', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'show'])->name('admin.payments.show');
});

// Admin Authentication Routes

Route::get('admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::prefix('admin')->middleware(['auth:admin', 'admin.guard'])->group(function () {

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    });

    Route::get('/admins', [AdminUserController::class, 'index'])
        ->name('admin.admins.index')
        ->middleware('permission:admins.view');

    Route::post('/admins', [AdminUserController::class, 'store'])
        ->name('admin.admins.store')
        ->middleware('permission:admins.create');

    Route::put('/admins/{admin}', [AdminUserController::class, 'update'])
        ->name('admin.admins.update')
        ->middleware('permission:admins.update');

    Route::delete('/admins/{admin}', [AdminUserController::class, 'destroy'])
        ->name('admin.admins.destroy')
        ->middleware('permission:admins.delete');

    // Roles
    Route::get('/roles', [RolePageController::class, 'index'])->name('admin.roles.index');
    Route::post('/roles', [RolePageController::class, 'store'])->name('admin.roles.store');
    Route::put('/roles/{role}', [RolePageController::class, 'update'])->name('admin.roles.update');
    Route::delete('/roles/{role}', [RolePageController::class, 'destroy'])->name('admin.roles.destroy');

    // Permissions
    Route::get('/permissions', [PermissionPageController::class, 'index'])->name('admin.permissions.index');
    Route::post('/permissions', [PermissionPageController::class, 'store'])->name('admin.permissions.store');
    Route::put('/permissions/{permission}', [PermissionPageController::class, 'update'])->name('admin.permissions.update');
    Route::delete('/permissions/{permission}', [PermissionPageController::class, 'destroy'])->name('admin.permissions.destroy');
});
// users Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Reservation cancel web route
Route::middleware(['auth'])->group(function () {
    Route::post('/reservations/{id}/cancel', [UniteReservationController::class, 'cancel'])
        ->name('reservations.cancel');
});

// department routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index')
        ->middleware('permission:departments.view');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->name('departments.store')
        ->middleware('permission:departments.create');

    Route::get('/departments/{id}', [DepartmentController::class, 'show'])
        ->name('departments.show')
        ->middleware('permission:departments.view');

    Route::put('/departments/{id}', [DepartmentController::class, 'update'])
        ->name('departments.update')
        ->middleware('permission:departments.update');

    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])
        ->name('departments.destroy')
        ->middleware('permission:departments.delete');
});

// property routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/property-packages', [PropertyPackageController::class, 'index'])
        ->name('property-packages.index')
        ->middleware('permission:property_packages.view');

    Route::post('/property-packages', [PropertyPackageController::class, 'store'])
        ->name('property-packages.store')
        ->middleware('permission:property_packages.create');

    Route::get('/property-packages/{id}', [PropertyPackageController::class, 'show'])
        ->name('property-packages.show')
        ->middleware('permission:property_packages.view');

    Route::put('/property-packages/{id}', [PropertyPackageController::class, 'update'])
        ->name('property-packages.update')
        ->middleware('permission:property_packages.update');

    Route::delete('/property-packages/{id}', [PropertyPackageController::class, 'destroy'])
        ->name('property-packages.destroy')
        ->middleware('permission:property_packages.delete');

    //  ad packages routes
    Route::get('/ad-packages', [AdPackageController::class, 'index'])
        ->name('ad-packages.index')
        ->middleware('permission:ad_packages.view');

    Route::post('/ad-packages', [AdPackageController::class, 'store'])
        ->name('ad-packages.store')
        ->middleware('permission:ad_packages.create');

    Route::get('/ad-packages/{id}', [AdPackageController::class, 'show'])
        ->name('ad-packages.show')
        ->middleware('permission:ad_packages.view');

    Route::put('/ad-packages/{id}', [AdPackageController::class, 'update'])
        ->name('ad-packages.update')
        ->middleware('permission:ad_packages.update');

    Route::delete('/ad-packages/{id}', [AdPackageController::class, 'destroy'])
        ->name('ad-packages.destroy')
        ->middleware('permission:ad_packages.delete');
});
// subscription routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])
        ->name('subscriptions.index')
        ->middleware('permission:subscriptions.view');

    Route::post('/subscriptions', [SubscriptionController::class, 'store'])
        ->name('subscriptions.store')
        ->middleware('permission:subscriptions.create');

    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show'])
        ->name('subscriptions.show')
        ->middleware('permission:subscriptions.view');

    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update'])
        ->name('subscriptions.update')
        ->middleware('permission:subscriptions.update');

    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy'])
        ->name('subscriptions.destroy')
        ->middleware('permission:subscriptions.delete');
});

// detail routes — DEPRECATED & REMOVED. The same UniteDetail fields are now
// edited inline as part of the main unite create/edit/show forms
// (see resources/views/dashboard/web/unites/_detail_fields.blade.php) and
// written via UniteRepository::update()'s nested $data[$data['type']]
// payload on every PUT /unites/{unite}. This standalone CRUD interface
// was a leftover from before the venue-detail consolidation and had no
// remaining callers — confirmed via full-repo reference scan before removal.

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    // Standalone unite-details CRUD removed — see deprecation note above.
});

// unite features routes

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/unites/{unite}/features', [UniteFeatureController::class, 'index'])
        ->name('unite-features.index')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/features/create', [UniteFeatureController::class, 'create'])
        ->name('unite-features.create')
        ->middleware('permission:unites.create');

    Route::post('/unites/{unite}/features', [UniteFeatureController::class, 'store'])
        ->name('unite-features.store')
        ->middleware('permission:unites.create');

    Route::get('/unites/{unite}/features/{feature}', [UniteFeatureController::class, 'show'])
        ->name('unite-features.show')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/features/{feature}/edit', [UniteFeatureController::class, 'edit'])
        ->name('unite-features.edit')
        ->middleware('permission:unites.update');

    Route::put('/unites/{unite}/features/{feature}', [UniteFeatureController::class, 'update'])
        ->name('unite-features.update')
        ->middleware('permission:unites.update');

    Route::delete('/unites/{unite}/features/{feature}', [UniteFeatureController::class, 'destroy'])
        ->name('unite-features.destroy')
        ->middleware('permission:unites.delete');
});

// unite packages routes
Route::get('unites/{unite}/packages', [UnitePackageController::class, 'index']);
Route::get('unites/{unite}/packages/{package}', [UnitePackageController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/packages', [UnitePackageController::class, 'store']);
    Route::put('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::patch('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::delete('unites/{unite}/packages/{package}', [UnitePackageController::class, 'destroy']);
});

// unite offers routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/unites/{unite}/offers', [UniteOfferController::class, 'index'])
        ->name('unite-offers.index')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/offers/create', [UniteOfferController::class, 'create'])
        ->name('unite-offers.create')
        ->middleware('permission:unites.create');

    Route::post('/unites/{unite}/offers', [UniteOfferController::class, 'store'])
        ->name('unite-offers.store')
        ->middleware('permission:unites.create');

    Route::get('/unites/{unite}/offers/{offer}', [UniteOfferController::class, 'show'])
        ->name('unite-offers.show')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/offers/{offer}/edit', [UniteOfferController::class, 'edit'])
        ->name('unite-offers.edit')
        ->middleware('permission:unites.update');

    Route::put('/unites/{unite}/offers/{offer}', [UniteOfferController::class, 'update'])
        ->name('unite-offers.update')
        ->middleware('permission:unites.update');

    Route::delete('/unites/{unite}/offers/{offer}', [UniteOfferController::class, 'destroy'])
        ->name('unite-offers.destroy')
        ->middleware('permission:unites.delete');
});

// unite slots routes

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/unites/{unite}/slots', [UniteSlotController::class, 'index'])
        ->name('unite-slots.index')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/slots/create', [UniteSlotController::class, 'create'])
        ->name('unite-slots.create')
        ->middleware('permission:unites.create');

    Route::post('/unites/{unite}/slots', [UniteSlotController::class, 'store'])
        ->name('unite-slots.store')
        ->middleware('permission:unites.create');

    Route::get('/unites/{unite}/slots/{slot}', [UniteSlotController::class, 'show'])
        ->name('unite-slots.show')
        ->middleware('permission:unites.view');

    Route::get('/unites/{unite}/slots/{slot}/edit', [UniteSlotController::class, 'edit'])
        ->name('unite-slots.edit')
        ->middleware('permission:unites.update');

    Route::put('/unites/{unite}/slots/{slot}', [UniteSlotController::class, 'update'])
        ->name('unite-slots.update')
        ->middleware('permission:unites.update');

    Route::delete('/unites/{unite}/slots/{slot}', [UniteSlotController::class, 'destroy'])
        ->name('unite-slots.destroy')
        ->middleware('permission:unites.delete');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/service-groups', [ServiceGroupController::class, 'index'])->name('service-groups.index');
    Route::post('/service-groups', [ServiceGroupController::class, 'store'])->name('service-groups.store');
    Route::get('/service-groups/{service_group}', [ServiceGroupController::class, 'show'])->name('service-groups.show');
    Route::put('/service-groups/{service_group}', [ServiceGroupController::class, 'update'])->name('service-groups.update');
    Route::delete('/service-groups/{service_group}', [ServiceGroupController::class, 'destroy'])->name('service-groups.destroy');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
});

// stadium types routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/stadium_types', [StadiumTypeController::class, 'index'])->name('stadium_types.index');
    Route::post('/stadium_types', [StadiumTypeController::class, 'store'])->name('stadium_types.store');
    Route::get('/stadium_types/{id}', [StadiumTypeController::class, 'show'])->name('stadium_types.show');
    Route::put('/stadium_types/{id}', [StadiumTypeController::class, 'update'])->name('stadium_types.update');
    Route::delete('/stadium_types/{id}', [StadiumTypeController::class, 'destroy'])->name('stadium_types.destroy');
});

// insurance policies routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/insurance_policies', [InsurancePolicyController::class, 'index'])->name('insurance_policies.index');
    Route::post('/insurance_policies', [InsurancePolicyController::class, 'store'])->name('insurance_policies.store');
    Route::get('/insurance_policies/{id}', [InsurancePolicyController::class, 'show'])->name('insurance_policies.show');
    Route::put('/insurance_policies/{id}', [InsurancePolicyController::class, 'update'])->name('insurance_policies.update');
    Route::delete('/insurance_policies/{id}', [InsurancePolicyController::class, 'destroy'])->name('insurance_policies.destroy');
});

// Suggestion Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');
    Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
    Route::get('/suggestions/{id}', [SuggestionController::class, 'show'])->name('suggestions.show');
    Route::put('/suggestions/{id}', [SuggestionController::class, 'update'])->name('suggestions.update');
    Route::delete('/suggestions/{id}', [SuggestionController::class, 'destroy'])->name('suggestions.destroy');
});

// Provider Dashboard (session auth via sanctum stateful)
Route::prefix('provider')->name('provider.')->middleware(['auth'])->group(function () {
    // Provider self-statistics (used by the dashboard JS widget)
    Route::get('/api/statistics', function (\Illuminate\Http\Request $request) {
        $controller = app(\App\Http\Controllers\Api\ProviderStatisticsController::class);

        return $controller($request);
    })->name('api.statistics');
    Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');
    Route::get('/approvals', [ProviderDashboardController::class, 'approvals'])->name('approvals');
    Route::get('/venues', [ProviderDashboardController::class, 'venues'])->name('venues');
    Route::get('/revenue', [ProviderDashboardController::class, 'revenue'])->name('revenue');
    Route::get('/transfers', [ProviderDashboardController::class, 'transfers'])->name('transfers');
    Route::post('/transfers/request', [ProviderDashboardController::class, 'requestTransfer'])->name('transfers.request');
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});

// Admin Transfer Management
Route::prefix('admin')->name('admin.transfers.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    // Transfer Policies
    Route::get('/transfer-policies', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyIndex'])->name('policy.index');
    Route::get('/transfer-policies/create', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyCreate'])->name('policy.create');
    Route::post('/transfer-policies', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyStore'])->name('policy.store');
    Route::get('/transfer-policies/{policy}/edit', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyEdit'])->name('policy.edit');
    Route::put('/transfer-policies/{policy}', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyUpdate'])->name('policy.update');
    Route::delete('/transfer-policies/{policy}', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'policyDestroy'])->name('policy.destroy');
    // Transfers
    Route::get('/transfers', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'index'])->name('index');
    Route::get('/transfers/create', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'create'])->name('create');
    Route::post('/transfers', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'store'])->name('store');
    Route::get('/transfers/{transfer}/edit', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'edit'])->name('edit');
    Route::put('/transfers/{transfer}', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'update'])->name('update');
    Route::delete('/transfers/{transfer}', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'destroy'])->name('destroy');
    // Transfer Requests
    Route::get('/transfer-requests', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'requests'])->name('requests');
    Route::post('/transfer-requests/{transferRequest}/approve', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'approveRequest'])->name('approve-request');
    Route::post('/transfer-requests/{transferRequest}/reject', [\App\Http\Controllers\Admin\Transfer\AdminTransferController::class, 'rejectRequest'])->name('reject-request');
});

// Admin Ads management
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::resource('ads', AdminAdController::class);
});

// Analytics dashboard
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

// Admin Reports
Route::prefix('admin')->name('admin.reports.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/reports', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'index'])->name('index');
    Route::get('/reports/revenue', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'revenue'])->name('revenue');
    Route::get('/reports/reservations', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'reservations'])->name('reservations');
    Route::get('/reports/users', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'users'])->name('users');
    Route::get('/reports/subscriptions', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/reports/venues', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'venues'])->name('venues');
    Route::get('/reports/transfers', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'transfers'])->name('transfers');
    Route::get('/reports/export', [\App\Http\Controllers\Admin\Reports\AdminReportsController::class, 'export'])->name('export');
});

// Provider Reports
Route::prefix('provider')->name('provider.reports.')->middleware(['auth'])->group(function () {
    Route::get('/reports', [\App\Http\Controllers\Provider\Dashboard\ProviderReportsController::class, 'index'])->name('index');
    Route::get('/reports/revenue', [\App\Http\Controllers\Provider\Dashboard\ProviderReportsController::class, 'revenue'])->name('revenue');
    Route::get('/reports/reservations', [\App\Http\Controllers\Provider\Dashboard\ProviderReportsController::class, 'reservations'])->name('reservations');
    Route::get('/reports/venues', [\App\Http\Controllers\Provider\Dashboard\ProviderReportsController::class, 'venues'])->name('venues');
    Route::get('/reports/export', [\App\Http\Controllers\Provider\Dashboard\ProviderReportsController::class, 'export'])->name('export');
});

// Broadcast / promotion notifications
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/broadcast', [BroadcastNotificationController::class, 'index'])->name('broadcast.index');
    Route::post('/broadcast', [BroadcastNotificationController::class, 'send'])->name('broadcast.send');
    Route::post('/notifications/test', [BroadcastNotificationController::class, 'test'])->name('broadcast.test');
    Route::get('/users/search', [BroadcastNotificationController::class, 'searchUsers'])->name('broadcast.users.search');
});

// Reservations admin page
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
});

// Reviewer management
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/reviewers', [ReviewerController::class, 'index'])->name('reviewers.index');
    Route::post('/reviewers', [ReviewerController::class, 'store'])->name('reviewers.store');
    Route::put('/reviewers/{reviewer}', [ReviewerController::class, 'update'])->name('reviewers.update');
    Route::delete('/reviewers/{reviewer}', [ReviewerController::class, 'destroy'])->name('reviewers.destroy');
});

// Promo code routes
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.guard'])->group(function () {
    Route::get('/promo-codes', [PromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::post('/promo-codes', [PromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::get('/promo-codes/{promoCode}', [PromoCodeController::class, 'show'])->name('promo-codes.show');
    Route::put('/promo-codes/{promoCode}', [PromoCodeController::class, 'update'])->name('promo-codes.update');
    Route::delete('/promo-codes/{promoCode}', [PromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
    Route::patch('/promo-codes/{promoCode}/toggle', [PromoCodeController::class, 'toggle'])->name('promo-codes.toggle');
});
