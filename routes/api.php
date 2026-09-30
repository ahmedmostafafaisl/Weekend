<?php

use App\Http\Controllers\Admin\Ads\AdCommentController;
use App\Http\Controllers\Admin\Ads\AdController;
use App\Http\Controllers\Admin\InsurancePolicy\InsurancePolicyController;
use App\Http\Controllers\Admin\Packages\AdPackageController;
use App\Http\Controllers\Admin\Packages\PropertyPackageController;
use App\Http\Controllers\Admin\Payment\PaymentController;
use App\Http\Controllers\Admin\Permission\PermissionController;
use App\Http\Controllers\Admin\Role\RoleController;
use App\Http\Controllers\Admin\Service\ServiceController;
use App\Http\Controllers\Admin\Service\ServiceGroupController;
use App\Http\Controllers\Admin\StadiumType\StadiumTypeController;
use App\Http\Controllers\Admin\Subscription\SubscriptionController;
use App\Http\Controllers\Admin\Suggestion\SuggestionController;
use App\Http\Controllers\Admin\Unite\UniteFeatureController;
use App\Http\Controllers\Admin\Unite\UniteOfferController;
use App\Http\Controllers\Admin\Unite\UnitePackageController;
use App\Http\Controllers\Admin\Unite\UnitePriceController;
use App\Http\Controllers\Admin\Unite\UniteSlotController;
use App\Http\Controllers\Api\MultiBookingController;
use App\Http\Controllers\Api\MySubscriptionController;
use App\Http\Controllers\Api\NearbyUniteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PackageDiscoveryController;
use App\Http\Controllers\Api\PromoCodeApiController;
use App\Http\Controllers\Api\ProviderStatisticsController;
use App\Http\Controllers\Api\SaudiCityController;
use App\Http\Controllers\Api\ServiceFeeController;
use App\Http\Controllers\Api\TransferApiController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\Department\DepartmentController;
use App\Http\Controllers\Provider\Unite\UniteController;
use App\Http\Controllers\Reservation\UniteReservationController;
use App\Http\Controllers\Unite\AvailabilityController;
use App\Http\Controllers\Viewing\UniteViewingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Structure (top-down):
|   1. Public auth (register, login, password reset)
|   2. Public read — departments, unites, reference data, payment callbacks
|   3. Authenticated routes (auth:sanctum) — one group per domain
|   4. Admin-guarded routes (auth:admin)
|
*/

// ═══════════════════════════════════════════════════════════════════════════
// 1. PUBLIC AUTH
// ═══════════════════════════════════════════════════════════════════════════

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

// ═══════════════════════════════════════════════════════════════════════════
// 2. PUBLIC READ ROUTES
// ═══════════════════════════════════════════════════════════════════════════

// ── Departments (public browsing) ────────────────────────────────────────
// Literal segments must be registered BEFORE the {department} wildcard so
// Laravel's top-down matcher doesn't swallow them as an ID.
Route::get('departments/browse', [DepartmentController::class, 'browse']);
Route::get('departments/{department}/unites', [DepartmentController::class, 'unites']);
Route::get('departments/{department}/available-unites', [MultiBookingController::class, 'availableUnites'])
    ->name('multi-booking.available');

// ── Unites (public read) ─────────────────────────────────────────────────
// Literal segments and nested routes BEFORE the {unite} wildcard.
Route::get('unites', [UniteController::class, 'index']);
Route::get('unites/search', [UniteController::class, 'search']);
Route::get('unites/nearby', NearbyUniteController::class);
Route::get('unites2', [UniteController::class, 'index2']);
Route::get('unites/{unite}/availability/date', [AvailabilityController::class, 'date']);
Route::get('unites/{unite}/availability/range', [AvailabilityController::class, 'range']);
Route::get('unites/{unite}/availability', [AvailabilityController::class, 'month']);
Route::get('unites/{unite}/features', [UniteFeatureController::class, 'index']);
Route::get('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'show']);
Route::get('unites/{unite}/packages', [UnitePackageController::class, 'index']);
Route::get('unites/{unite}/packages/{package}', [UnitePackageController::class, 'show']);
Route::get('unites/{unite}/offers', [UniteOfferController::class, 'index']);
Route::get('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'show']);
Route::get('unites/{unite}/prices', [UnitePriceController::class, 'index']);
Route::get('unites/{unite}/prices/{price}', [UnitePriceController::class, 'show']);
Route::get('unites/{unite}/slots', [UniteSlotController::class, 'index']);
Route::get('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'show']);
Route::get('unites/{unite}', [UniteController::class, 'show']); // wildcard last

// ── Public misc ──────────────────────────────────────────────────────────
Route::get('/home', [PackageDiscoveryController::class, 'home'])->name('home');
Route::get('/payment-methods', [PaymentController::class, 'paymentMethods']);
Route::get('/saudi-cities', [SaudiCityController::class, 'index']);
Route::get('/service-fees', [ServiceFeeController::class, 'index']);
Route::post('promo-codes/validate', [PromoCodeApiController::class, 'check'])->middleware('throttle:promo');

// ── Public reference data ────────────────────────────────────────────────
Route::get('service-groups', [ServiceGroupController::class, 'index']);
Route::get('service-groups/{id}', [ServiceGroupController::class, 'show']);
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show']);
Route::get('stadium-types', [StadiumTypeController::class, 'index']);
Route::get('stadium-types/{id}', [StadiumTypeController::class, 'show']);
Route::get('insurance-policies', [InsurancePolicyController::class, 'index']);
Route::get('insurance-policies/{id}', [InsurancePolicyController::class, 'show']);
Route::get('suggestions', [SuggestionController::class, 'index']);
Route::get('suggestions/{id}', [SuggestionController::class, 'show']);

// ── Ad comments (public read) ────────────────────────────────────────────
Route::get('/ads/{ad}/comments', [AdCommentController::class, 'index']);

// ── Payment gateway callbacks (no auth — called by gateway servers) ───────
Route::match(['GET', 'POST'], '/geidea/payment/callback', [PaymentController::class, 'callBack'])
    ->name('payment.callback');
Route::post('/tappy/callback', [PaymentController::class, 'tappyCallback']);
Route::post('/tamara/callback', [PaymentController::class, 'tamaraCallback'])
    ->name('payment.tamara.notification');
Route::post('/maysar/callback', [PaymentController::class, 'maysarCallback']);

// ═══════════════════════════════════════════════════════════════════════════
// 3. AUTHENTICATED ROUTES (auth:sanctum)
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/fcm/token', [AuthController::class, 'updateFcmToken'])->name('fcm.token');

    // ── Profile ───────────────────────────────────────────────────────────
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::post('/profile/photo', [UserProfileController::class, 'updatePhoto']);
    Route::delete('/profile', [UserProfileController::class, 'deactivate']);

    // ── Notifications ─────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences/{type}', [NotificationPreferenceController::class, 'update']);

    // ── Departments (write) ───────────────────────────────────────────────
    Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);

    // ── Unites (write + social) ───────────────────────────────────────────
    Route::post('unites', [UniteController::class, 'store']);
    Route::put('unites/{unite}', [UniteController::class, 'update']);
    Route::patch('unites/{unite}', [UniteController::class, 'update']);
    Route::delete('unites/{unite}', [UniteController::class, 'destroy']);
    Route::post('/unites/{unite}/favorite', [UniteController::class, 'toggleFavorite']);
    Route::post('/unites/{unite}/rate', [UniteController::class, 'rate']);
    Route::post('/vendors/{id}/rate', [UniteController::class, 'rateVendor']);
    Route::get('/user/favorites', [UniteController::class, 'userFavorites']);

    // ── Unite sub-resources (write) ────────────────────────────────────────
    Route::post('unites/{unite}/features', [UniteFeatureController::class, 'store']);
    Route::put('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'update']);
    Route::patch('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'update']);
    Route::delete('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'destroy']);

    Route::post('unites/{unite}/packages', [UnitePackageController::class, 'store']);
    Route::put('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::patch('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::delete('unites/{unite}/packages/{package}', [UnitePackageController::class, 'destroy']);

    Route::post('unites/{unite}/offers', [UniteOfferController::class, 'store']);
    Route::put('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'update']);
    Route::patch('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'update']);
    Route::delete('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'destroy']);

    Route::post('unites/{unite}/prices', [UnitePriceController::class, 'store']);
    Route::put('unites/{unite}/prices/{price}', [UnitePriceController::class, 'update']);
    Route::patch('unites/{unite}/prices/{price}', [UnitePriceController::class, 'update']);
    Route::delete('unites/{unite}/prices/{price}', [UnitePriceController::class, 'destroy']);

    Route::post('unites/{unite}/slots', [UniteSlotController::class, 'store']);
    Route::put('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'update']);
    Route::patch('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'update']);
    Route::delete('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'destroy']);

    // ── Booking availability (admin/provider — slot config tool) ───────────
    Route::get('unites/{unite}/booking-availability', [UniteSlotController::class, 'availabilityAndPrices']);

    // ── Reservations ──────────────────────────────────────────────────────
    Route::apiResource('reservations', UniteReservationController::class);
    Route::post('/reservations/{id}/cancel', [UniteReservationController::class, 'cancel']);
    Route::post('/reservations/{id}/approve', [UniteReservationController::class, 'approve']);
    Route::post('/reservations/{id}/reject', [UniteReservationController::class, 'reject']);
    Route::post('/reservations/{id}/rate', [UniteReservationController::class, 'rate']);
    Route::get('/my-reservations', [UniteReservationController::class, 'myReservations'])
        ->name('reservations.my');

    // ── Multi-unit booking ────────────────────────────────────────────────
    Route::post('multi-booking', [MultiBookingController::class, 'store'])->name('multi-booking.store');

    // ── Viewing appointments ──────────────────────────────────────────────
    Route::post('/unite-viewings', [UniteViewingController::class, 'store']);
    Route::post('/unite-viewings/{id}/cancel', [UniteViewingController::class, 'cancel']);

    // ── Packages & subscriptions ──────────────────────────────────────────
    Route::apiResource('property-packages', PropertyPackageController::class);
    Route::apiResource('ad-packages', AdPackageController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::get('/my-subscriptions', [MySubscriptionController::class, 'index'])->name('my-subscriptions.index');
    Route::get('/my-subscriptions/{id}', [MySubscriptionController::class, 'show'])->name('my-subscriptions.show');
    Route::get('package-activation-keys', [PackageDiscoveryController::class, 'activationKeys'])
        ->name('packages.activation-keys');
    Route::get('user-subscriptions', [PackageDiscoveryController::class, 'userSubscriptions'])
        ->name('packages.user-subscriptions');

    // ── Ads ───────────────────────────────────────────────────────────────
    Route::apiResource('ads', AdController::class);
    Route::get('/user/ads', [AdController::class, 'userAds']);
    Route::post('/ads/{ad}/seen', [AdController::class, 'markSeen']);
    Route::post('/ads/{ad}/activate', [AdController::class, 'activate']);
    Route::post('/ads/{ad}/comments', [AdCommentController::class, 'store']);
    Route::delete('/ads/{ad}/comments/{comment}', [AdCommentController::class, 'destroy']);
    Route::patch('/ads/{ad}/comments/{comment}/toggle', [AdCommentController::class, 'toggle']);
    Route::get('/ads/{ad}/comments/my', [AdCommentController::class, 'myComments']);

    // ── Transfers & payouts ───────────────────────────────────────────────
    Route::get('/transfer-policy', [TransferApiController::class, 'policy']);
    Route::get('/refund-policy', [TransferApiController::class, 'refundPolicy']);
    Route::get('/my-transfers', [TransferApiController::class, 'myTransfers']);
    Route::post('/transfer-requests', [TransferApiController::class, 'requestTransfer']);
    Route::get('/transfer-requests', [TransferApiController::class, 'myRequests']);

    // ── Payments ──────────────────────────────────────────────────────────
    Route::post('/geidea/payment/process', [PaymentController::class, 'paymentProcess'])
        ->name('payment.process');
    Route::get('/geidea/payments/{payment_id}', [PaymentController::class, 'details'])
        ->name('payment.details');
    Route::get('/my-payments', [PaymentController::class, 'myPayments'])
        ->name('payment.my');
    Route::post('/payments/confirm-by-order', [PaymentController::class, 'confirmByOrder'])
        ->name('payment.confirm-by-order');
    Route::get('/payments', [PaymentController::class, 'index'])
        ->name('payment.index');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])
        ->name('payment.show');

    // ── Misc (write) ──────────────────────────────────────────────────────
    Route::post('service-groups', [ServiceGroupController::class, 'store']);
    Route::post('service-groups/{id}', [ServiceGroupController::class, 'update']);
    Route::delete('service-groups/{id}', [ServiceGroupController::class, 'destroy']);
    Route::post('services', [ServiceController::class, 'store']);
    Route::post('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'destroy']);
    Route::post('stadium-types', [StadiumTypeController::class, 'store']);
    Route::post('stadium-types/{id}', [StadiumTypeController::class, 'update']);
    Route::delete('stadium-types/{id}', [StadiumTypeController::class, 'destroy']);
    Route::post('insurance-policies', [InsurancePolicyController::class, 'store']);
    Route::post('insurance-policies/{id}', [InsurancePolicyController::class, 'update']);
    Route::delete('insurance-policies/{id}', [InsurancePolicyController::class, 'destroy']);
    Route::post('suggestions', [SuggestionController::class, 'store']);
    Route::post('suggestions/{id}', [SuggestionController::class, 'update']);
    Route::delete('suggestions/{id}', [SuggestionController::class, 'destroy']);
    Route::get('/my-suggestions', [SuggestionController::class, 'mySuggestions']);

    // ── Provider statistics ───────────────────────────────────────────────
    Route::get('/provider/statistics', ProviderStatisticsController::class)
        ->name('provider.statistics');
});

// ═══════════════════════════════════════════════════════════════════════════
// 4. ADMIN-GUARDED ROUTES (auth:admin)
// ═══════════════════════════════════════════════════════════════════════════

Route::middleware('auth:admin')->group(function () {
    Route::apiResource('admin/roles', RoleController::class);
    Route::apiResource('admin/permissions', PermissionController::class);

    Route::post('/admin/notifications/test', [
        \App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController::class, 'test',
    ])->name('admin.notifications.test')->middleware('permission:notifications.create');

    Route::post('/admin/notifications/test-token', [
        \App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController::class, 'testToken',
    ])->name('admin.notifications.test-token')->middleware('permission:notifications.create');

    Route::get('/admin/users/search', [
        \App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController::class, 'searchUsers',
    ])->name('admin.users.search');
});
