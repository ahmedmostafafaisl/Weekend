<?php

namespace App\Providers;

use App\Repositories\Ads\AdRepository;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\InsurancePolicy\InsurancePolicyRepository;
use App\Repositories\Interfaces\AdInterface;
use App\Repositories\Interfaces\AdPackageInterface;
use App\Repositories\Interfaces\DepartmentInterface;
use App\Repositories\Interfaces\InsurancePolicyRepositoryInterface;
use App\Repositories\Interfaces\PaymentGatewayInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Repositories\Interfaces\PropertyPackageInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\StadiumTypeRepositoryInterface;
use App\Repositories\Interfaces\SubscriptionInterface;
use App\Repositories\Interfaces\SuggestionRepositoryInterface;
use App\Repositories\Interfaces\UniteDetailInterface;
use App\Repositories\Interfaces\UniteFeatureInterface;
use App\Repositories\Interfaces\UniteOfferInterface;
use App\Repositories\Interfaces\UnitePackageInterface;
use App\Repositories\Interfaces\UnitePriceInterface;
use App\Repositories\Interfaces\UniteRepositoryInterface;
use App\Repositories\Interfaces\UniteReservationInterface;
use App\Repositories\Interfaces\UniteSlotInterface;
use App\Repositories\Interfaces\UserInterface;
use App\Repositories\Packages\AdPackageRepository;
use App\Repositories\Packages\PropertyPackageRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Reservation\UniteReservationRepository;
use App\Repositories\Role\DBPermissionRepository;
use App\Repositories\Role\DBRoleRepository;
use App\Repositories\StadiumType\StadiumTypeRepository;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Repositories\Suggestion\SuggestionRepository;
use App\Repositories\Unite\UniteDetailRepository;
use App\Repositories\Unite\UniteFeatureRepository;
use App\Repositories\Unite\UniteOfferRepository;
use App\Repositories\Unite\UnitePackageRepository;
use App\Repositories\Unite\UnitePriceRepository;
use App\Repositories\Unite\UniteRepository;
use App\Repositories\Unite\UniteSlotRepository;
use App\Repositories\User\UserRepository;
use App\Services\Payment\GeideaPaymentService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(DepartmentInterface::class, DepartmentRepository::class);
        $this->app->bind(UniteRepositoryInterface::class, UniteRepository::class);
        $this->app->bind(PropertyPackageInterface::class, PropertyPackageRepository::class);
        $this->app->bind(AdPackageInterface::class, AdPackageRepository::class);
        $this->app->bind(PropertyPackageInterface::class, PropertyPackageRepository::class);
        $this->app->bind(AdInterface::class, AdRepository::class);
        $this->app->bind(SubscriptionInterface::class, SubscriptionRepository::class);
        $this->app->bind(UniteOfferInterface::class, UniteOfferRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, DBRoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, DBPermissionRepository::class);
        $this->app->bind(UniteReservationInterface::class, UniteReservationRepository::class);
        $this->app->bind(UnitePackageInterface::class, UnitePackageRepository::class);
        $this->app->bind(UniteDetailInterface::class, UniteDetailRepository::class);
        $this->app->bind(UniteFeatureInterface::class, UniteFeatureRepository::class);
        $this->app->bind(UniteSlotInterface::class, UniteSlotRepository::class);
        $this->app->bind(UnitePriceInterface::class, UnitePriceRepository::class);
        $this->app->bind(StadiumTypeRepositoryInterface::class, StadiumTypeRepository::class);
        $this->app->bind(InsurancePolicyRepositoryInterface::class, InsurancePolicyRepository::class);
        $this->app->bind(SuggestionRepositoryInterface::class, SuggestionRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentGatewayInterface::class, GeideaPaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        $this->registerSoftDeleteMacros();
    }

    /**
     * Registers query-builder macros that exclude soft-deleted rows on raw
     * DB::table() queries.
     */
    private function registerSoftDeleteMacros(): void
    {
        QueryBuilder::macro('live', function () {
            /** @var QueryBuilder $this */
            return $this->whereNull($this->from.'.deleted_at');
        });
    }
}
