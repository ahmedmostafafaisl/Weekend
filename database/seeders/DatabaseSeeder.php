<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── 1. Auth & permissions ─────────────────────────────────────────
            AdminRolesPermissionsSeeder::class,   // roles, permissions (now includes transfer + ad_comments), admin accounts
            AdminsTableSeeder::class,             // additional admin accounts
            UsersTableSeeder::class,              // 5 providers + 20 customers

            // ── 2. Venue structure ────────────────────────────────────────────
            DepartmentsTableSeeder::class,        // 10 departments (stadium/hall/lounge/camp)
            UnitesTableSeeder::class,             // 27 venues across all departments
            UniteDetailsTableSeeder::class,       // UniteDetail (consolidated table for all venue types)
            UniteSlotsTableSeeder::class,         // 7-day slot configs per venue
            UnitePricesTableSeeder::class,        // weekday/Thu/Fri/Sat pricing
            UniteOffersTableSeeder::class,        // 3 offers per venue (active + past)
            UniteFeatureSeeder::class,            // NEW — 3-5 amenities per venue, type-specific, active/inactive mix
            UniteNewFeatureSeeder::class,         // NEW — "what's new" highlights on ~60% of venues
            UnitePackageSeeder::class,            // NEW — Bronze/Silver/Gold capacity packages per venue
            AdminReviewerScopeSeeder::class,      // NEW — 3 reviewer accounts covering all 3 scope modes

            // ── 3. Supporting data ────────────────────────────────────────────
            StadiumTypeSeeder::class,
            InsurancePolicySeeder::class,
            PropertyPackagesTableSeeder::class,
            AdPackagesTableSeeder::class,
            ServiceSeeder::class,

            // ── 4. Transactional data ─────────────────────────────────────────
            SubscriptionsTableSeeder::class,
            SubscriptionPaymentSeeder::class,     // NEW — matching Payment+PaymentItem per subscription, all 4 gateways
            AdsTableSeeder::class,
            AdViewsTableSeeder::class,
            AdCommentSeeder::class,               // NEW — 2–4 comments per ad (1 hidden to demo toggle)
            UniteReservationsTableSeeder::class,  // 25 reservations + payments per venue
            ProviderStatisticsSeeder::class,       // 12-month historical data for provider stats API
            FavoriteUnitesTableSeeder::class,
            UniteRatingsTableSeeder::class,
            UniteViewsTableSeeder::class,
            VendorRatingsTableSeeder::class,
            UserNotificationPreferenceSeeder::class, // NEW — all 9 types across ~70% of users, varied on/off

            // ── 5. Promo codes ────────────────────────────────────────────────
            PromoCodeSeeder::class,               // 11 codes (active/expired/future)
            PromoCodeUsageSeeder::class,          // NEW — usage records against real paid payments

            // ── 6. Fund transfers (new) ───────────────────────────────────────
            TransferPolicySeeder::class,          // NEW — 3 policies (1 active)
            ProviderTransferSeeder::class,        // NEW — completed + pending transfers per provider
            TransferRequestSeeder::class,         // NEW — 1 approved + 1 pending request per provider

            // ── 7. Notifications ──────────────────────────────────────────────
            NotificationSeeder::class,            // NEW — sample notifications for first 5 customers

            // ── 8. Misc ───────────────────────────────────────────────────────
            SuggestionSeeder::class,
        ]);
    }
}
