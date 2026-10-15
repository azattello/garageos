<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Client;
use App\Models\Order;
use App\Policies\ClientPolicy;
use App\Policies\OrderPolicy;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Organization;
use App\Models\Service;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Part;
use App\Models\ServiceCategory;
use App\Models\AppointmentStatus;
use App\Models\UserInvite;
use App\Models\User;
use App\Models\SubscriptionChangeRequest;
use App\Policies\AppointmentPolicy;
use App\Policies\CarPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ServicePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\PartPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\AppointmentStatusPolicy;
use App\Policies\UserInvitePolicy;
use App\Policies\UserPolicy;
use App\Policies\SubscriptionChangeRequestPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Car::class, CarPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(Part::class, PartPolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
        Gate::policy(AppointmentStatus::class, AppointmentStatusPolicy::class);
        Gate::policy(UserInvite::class, UserInvitePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(SubscriptionChangeRequest::class, SubscriptionChangeRequestPolicy::class);
    }
}
