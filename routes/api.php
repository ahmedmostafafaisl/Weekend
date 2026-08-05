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
use App\Http\Controllers\Api\NearbyUniteController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PromoCodeApiController;
use App\Http\Controllers\Api\ProviderStatisticsController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\Department\DepartmentController;
use App\Http\Controllers\Provider\Unite\UniteController;
use App\Http\Controllers\Reservation\UniteReservationController;
use App\Http\Controllers\Unite\AvailabilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Notification Inbox ───────────────────────────────────────────────────────
// GET    /api/notifications                — paginated list (unread_only=1 filter)
// GET    /api/notifications/unread-count   — lightweight badge count
// POST   /api/notifications/{id}/read      — mark single as read
// POST   /api/notifications/read-all       — mark all as read
// DELETE /api/notifications/{id}           — delete single
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);

    // Notification preferences — per-type push + email on/off
    // GET  /api/notification-preferences          — all types with current setting
    // PUT  /api/notification-preferences/{type}   — update single type
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences/{type}', [NotificationPreferenceController::class, 'update']);
});

// ── Payment Methods ──────────────────────────────────────────────────────────
// Available payment gateways for checkout screen
Route::get('/payment-methods', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'paymentMethods']);

// ── Transfer Policy & Transfers (Provider) ────────────────────────────────────
// GET  /api/transfer-policy         — active fund transfer policy (providers only)
// GET  /api/my-transfers            — provider's received transfers
// POST /api/transfer-requests       — provider requests a payout
// GET  /api/transfer-requests       — provider sees their own requests
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transfer-policy', [\App\Http\Controllers\Api\TransferApiController::class, 'policy']);
    Route::get('/refund-policy', [\App\Http\Controllers\Api\TransferApiController::class, 'refundPolicy']);
    Route::get('/my-transfers', [\App\Http\Controllers\Api\TransferApiController::class, 'myTransfers']);
    Route::post('/transfer-requests', [\App\Http\Controllers\Api\TransferApiController::class, 'requestTransfer']);
    Route::get('/transfer-requests', [\App\Http\Controllers\Api\TransferApiController::class, 'myRequests']);
});

// ── Payment gateway callbacks ─────────────────────────────────────────────────
Route::post('/tappy/callback', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'tappyCallback']);
Route::post('/tamara/callback', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'tamaraCallback'])->name('payment.tamara.notification');
Route::post('/maysar/callback', [\App\Http\Controllers\Admin\Payment\PaymentController::class, 'maysarCallback']);

// ── User Profile API ──────────────────────────────────────────────────────────
// GET    /api/profile           — get own profile
// PUT    /api/profile           — update profile fields (name, email, phone, password…)
// POST   /api/profile/photo     — upload/replace profile photo (multipart)
// DELETE /api/profile           — deactivate account (requires password confirmation)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::post('/profile/photo', [UserProfileController::class, 'updatePhoto']);
    Route::delete('/profile', [UserProfileController::class, 'deactivate']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Department Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('departments', DepartmentController::class);
});

// Unite Routes

Route::get('unites', [UniteController::class, 'index']);
// /unites/search must come before /unites/{unite} — 'search' is a literal segment
Route::get('unites/search', [UniteController::class, 'search']);

// Nearby venues — literal segment, must come before the {unite} wildcard
// GET /api/unites/nearby?lat=24.7135&lng=46.6753&radius_km=10&type=hall&limit=20
Route::get('unites/nearby', NearbyUniteController::class);

// Specific routes BEFORE the {unite} wildcard — Laravel matches top-down.
// Without this order, 'unites/4/availability' is swallowed by 'unites/{unite}'.
Route::get('unites/{unite}/availability/date', [AvailabilityController::class, 'date']);
Route::get('unites/{unite}/availability', [AvailabilityController::class, 'month']);

// Generic wildcard — must be last among GET /unites/* routes
Route::get('unites/{unite}', [UniteController::class, 'show']);

// Promo code validation — public, no auth required
// POST /api/promo-codes/validate  { "code": "SUMMER20", "amount": 500.00 }
Route::post('promo-codes/validate', [PromoCodeApiController::class, 'check']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites', [UniteController::class, 'store']);
    Route::put('unites/{unite}', [UniteController::class, 'update']);
    Route::patch('unites/{unite}', [UniteController::class, 'update']);
    Route::delete('unites/{unite}', [UniteController::class, 'destroy']);
});

Route::get('unites2', [UniteController::class, 'index2']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/unites/{id}/favorite', [UniteController::class, 'toggleFavorite']);
    Route::post('/unites/{id}/rate', [UniteController::class, 'rate']);
    Route::post('/vendors/{id}/rate', [UniteController::class, 'rateVendor']);
    Route::get('/user/favorites', [UniteController::class, 'userFavorites']);

});

// // Unite Offer Routes
// Route::apiResource('unite_offers', UniteOfferController::class);
// // get offers by unite id
Route::get('unite_offers/unite/{id}', [UniteOfferController::class, 'findByUniteId'])->name('unite_offers.findByUniteId');

// Property Package Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('property-packages', PropertyPackageController::class);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('ad-packages', AdPackageController::class);
});

//  Package Routes
Route::get('all-packages', [PropertyPackageController::class, 'getAllPackages'])->name('packages.all');

// Ads Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('ads', AdController::class);
    Route::get('/user/ads', [AdController::class, 'userAds']);
    Route::post('/ads/{id}/seen', [AdController::class, 'markSeen']);
    Route::post('/ads/{id}/activate', [AdController::class, 'activate']);
});

// Ad Comments
// GET    /api/ads/{ad}/comments           — public: visible comments (owner sees all)
// POST   /api/ads/{ad}/comments           — auth: post a comment
// DELETE /api/ads/{ad}/comments/{comment} — auth: delete own comment OR ad owner deletes any
// PATCH  /api/ads/{ad}/comments/{comment}/toggle — ad owner only: hide/show comment
// GET    /api/ads/{ad}/comments/my        — auth: current user's own comments on this ad
Route::get('/ads/{ad}/comments', [AdCommentController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ads/{ad}/comments', [AdCommentController::class, 'store']);
    Route::delete('/ads/{ad}/comments/{comment}', [AdCommentController::class, 'destroy']);
    Route::patch('/ads/{ad}/comments/{comment}/toggle', [AdCommentController::class, 'toggle']);
    Route::get('/ads/{ad}/comments/my', [AdCommentController::class, 'myComments']);
});

// Subscription Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('subscriptions', SubscriptionController::class);
});
// My Subscriptions — authenticated user's own subscription history
// GET /api/my-subscriptions          — current (active) + expired list
// GET /api/my-subscriptions/{id}     — single subscription detail
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-subscriptions', [\App\Http\Controllers\Api\MySubscriptionController::class, 'index'])->name('my-subscriptions.index');
    Route::get('/my-subscriptions/{id}', [\App\Http\Controllers\Api\MySubscriptionController::class, 'show'])->name('my-subscriptions.show');
});
Route::prefix('admin')
    ->middleware(['auth:admin'])
    ->group(function () {
        // CRUD Roles
        Route::apiResource('roles', RoleController::class);
        // CRUD Permissions
        Route::apiResource('permissions', PermissionController::class);
        // unites management
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('reservations', UniteReservationController::class);
    Route::post('/reservations/{id}/cancel', [UniteReservationController::class, 'cancel']);
    Route::post('/reservations/{id}/approve', [UniteReservationController::class, 'approve']);
    Route::post('/reservations/{id}/reject', [UniteReservationController::class, 'reject']);
    // Rate a completed booking — one rating per reservation, independent of
    // any other booking the same customer has made for the same venue.
    Route::post('/reservations/{id}/rate', [UniteReservationController::class, 'rate']);

    // Paginated reservation history for authenticated customer
    // GET /api/my-reservations?status=confirmed&upcoming=1&per_page=15
    Route::get('/my-reservations', [UniteReservationController::class, 'myReservations'])
        ->name('reservations.my');

    // Store/update FCM device token for push notifications
    // POST /api/fcm/token  { "fcm_token": "..." }
    Route::post('/fcm/token', [\App\Http\Controllers\Provider\AuthController::class, 'updateFcmToken'])
        ->name('fcm.token');
});

// Admin notification endpoints
Route::middleware('auth:admin')->group(function () {
    // POST /api/admin/notifications/test  { email/user_id, title, body, ... }
    Route::post('/admin/notifications/test', [\App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController::class, 'test'])
        ->name('admin.notifications.test');

    // GET /api/admin/users/search?q=ahmed&type=customer  — for specific-user picker
    Route::get('/admin/users/search', [\App\Http\Controllers\Admin\Broadcast\BroadcastNotificationController::class, 'searchUsers'])
        ->name('admin.users.search');
});

// Unite Detail Routes — DEPRECATED & REMOVED. GET /api/unites/{unite} already
// embeds the detail object inline (see SingleUniteResource::getDetailModel()),
// and POST/PUT /api/unites/{unite} already writes detail fields via the
// nested $data[$data['type']] payload in UniteRepository::update()/create().
// This standalone endpoint duplicated both the read and write paths with
// no unique capability beyond a narrow DELETE-detail-only case, and had
// zero other callers in the repo (confirmed via full reference scan).

// Unite Feature Routes
Route::get('unites/{unite}/features', [UniteFeatureController::class, 'index']);
Route::get('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/features', [UniteFeatureController::class, 'store']);
    Route::put('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'update']);
    Route::patch('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'update']);
    Route::delete('unites/{unite}/features/{feature}', [UniteFeatureController::class, 'destroy']);
});
// Unite Package Routes
Route::get('unites/{unite}/packages', [UnitePackageController::class, 'index']);
Route::get('unites/{unite}/packages/{package}', [UnitePackageController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/packages', [UnitePackageController::class, 'store']);
    Route::put('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::patch('unites/{unite}/packages/{package}', [UnitePackageController::class, 'update']);
    Route::delete('unites/{unite}/packages/{package}', [UnitePackageController::class, 'destroy']);
});

// Unite Offer Routes

Route::get('unites/{unite}/offers', [UniteOfferController::class, 'index']);
Route::get('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/offers', [UniteOfferController::class, 'store']);
    Route::put('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'update']);
    Route::patch('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'update']);
    Route::delete('unites/{unite}/offers/{offer}', [UniteOfferController::class, 'destroy']);
});

// unite price routes

Route::get('unites/{unite}/prices', [UnitePriceController::class, 'index']);
Route::get('unites/{unite}/prices/{price}', [UnitePriceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/prices', [UnitePriceController::class, 'store']);
    Route::put('unites/{unite}/prices/{price}', [UnitePriceController::class, 'update']);
    Route::patch('unites/{unite}/prices/{price}', [UnitePriceController::class, 'update']);
    Route::delete('unites/{unite}/prices/{price}', [UnitePriceController::class, 'destroy']);
});

// Unite Slot Routes

Route::get('unites/{unite}/slots', [UniteSlotController::class, 'index']);
Route::get('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'show']);
Route::get('unites/{unite}/booking-availability', [UniteSlotController::class, 'availabilityAndPrices']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('unites/{unite}/slots', [UniteSlotController::class, 'store']);
    Route::put('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'update']);
    Route::patch('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'update']);
    Route::delete('unites/{unite}/slots/{slot}', [UniteSlotController::class, 'destroy']);
});

Route::get('service-groups', [ServiceGroupController::class, 'index']);
Route::get('service-groups/{service_group}', [ServiceGroupController::class, 'show']);
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{service}', [ServiceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('service-groups', [ServiceGroupController::class, 'store']);
    Route::put('service-groups/{service_group}', [ServiceGroupController::class, 'update']);
    Route::delete('service-groups/{service_group}', [ServiceGroupController::class, 'destroy']);

    Route::post('services', [ServiceController::class, 'store']);
    Route::put('services/{service}', [ServiceController::class, 'update']);
    Route::delete('services/{service}', [ServiceController::class, 'destroy']);
});

// Saudi Cities Reference (static list — no auth required, matches the
// unite city field's validation and the dashboard city dropdown)
Route::get('/saudi-cities', [\App\Http\Controllers\Api\SaudiCityController::class, 'index']);

// Service fees — public reference data, so a client can show a price
// breakdown before checkout instead of only discovering the fee after
// paying. See App\Models\ServiceFee::feeFor() for where it's actually applied.
Route::get('/service-fees', [\App\Http\Controllers\Api\ServiceFeeController::class, 'index']);

// Stadium Type Routes
Route::prefix('stadium-types')->group(function () {
    Route::get('/', [StadiumTypeController::class, 'index']);
    Route::post('/', [StadiumTypeController::class, 'store']);
    Route::get('/{id}', [StadiumTypeController::class, 'show']);
    Route::post('/{id}', [StadiumTypeController::class, 'update']);
    Route::delete('/{id}', [StadiumTypeController::class, 'destroy']);
});

// Insurance Policy Routes
Route::prefix('insurance-policies')->group(function () {
    Route::get('/', [InsurancePolicyController::class, 'index']);
    Route::post('/', [InsurancePolicyController::class, 'store']);
    Route::get('/{id}', [InsurancePolicyController::class, 'show']);
    Route::post('/{id}', [InsurancePolicyController::class, 'update']);
    Route::delete('/{id}', [InsurancePolicyController::class, 'destroy']);
});

// Suggestion Routes
Route::prefix('suggestions')->group(function () {
    Route::get('/', [SuggestionController::class, 'index']);
    Route::post('/', [SuggestionController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/{id}', [SuggestionController::class, 'show']);
    Route::post('/{id}', [SuggestionController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [SuggestionController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-suggestions', [SuggestionController::class, 'mySuggestions']);
});

// -------------------------------------------------------------------------
// Payment routes
// -------------------------------------------------------------------------

// Webhook callback — must stay unauthenticated (called by Geidea's servers).
// Signature verification happens inside GeideaPaymentService::callBack().

// ── Provider Statistics (mobile app) ─────────────────────────────────────────
// GET /api/provider/statistics?year=2026&month=5
Route::middleware('auth:sanctum')->get('/provider/statistics', ProviderStatisticsController::class)
    ->name('provider.statistics');

Route::match(['GET', 'POST'], '/geidea/payment/callback', [PaymentController::class, 'callBack'])
    ->name('payment.callback');

Route::middleware('auth:sanctum')->group(function () {
    // Initiate a payment session → returns Geidea hosted URL
    Route::post('/geidea/payment/process', [PaymentController::class, 'paymentProcess'])
        ->name('payment.process');

    // Query a payment intent from Geidea by their paymentIntentId
    Route::get('/geidea/payments/{payment_id}', [PaymentController::class, 'details'])
        ->name('payment.details');

    // Provider: list own payments
    Route::get('/my-payments', [PaymentController::class, 'myPayments'])
        ->name('payment.my');

    // Recovery: manually confirm a payment using the Geidea Order ID
    // Use when callback was unreachable (localhost / no public URL)
    Route::post('/payments/confirm-by-order', [PaymentController::class, 'confirmByOrder'])
        ->name('payment.confirm-by-order');

    // Admin: list all payments / single payment
    Route::get('/payments', [PaymentController::class, 'index'])
        ->name('payment.index');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])
        ->name('payment.show');
});
