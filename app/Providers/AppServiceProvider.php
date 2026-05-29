<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Supply\SupplyServiceClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        Paginator::useBootstrapFive();

        Blade::directive('format', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::format($expression); ?>";
        });

        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->isSuperAdmin()) {
                return true;
            }

            if ($user instanceof User && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        View::composer('layouts.app', function ($view): void {
            $user = request()->user();
            $sidebarStoresMissingMapCount = 0;
            $sidebarProjectDraftCount = 0;

            if ($user instanceof User && trim((string) config('services.supply_service.base_url', '')) !== '') {
                try {
                    /** @var SupplyServiceClient $supplyServiceClient */
                    $supplyServiceClient = app(SupplyServiceClient::class);
                    $payload = $supplyServiceClient->storeSidebarSummary($user);
                    $sidebarStoresMissingMapCount = max(0, (int) data_get($payload, 'data.stores_missing_map_count', 0));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            if ($user instanceof User && trim((string) config('services.calculation_service.base_url', '')) !== '') {
                $sidebarProjectDraftCount = $this->resolveSidebarProjectDraftCount();
            }

            $view->with([
                'sidebarStoresMissingMapCount' => $sidebarStoresMissingMapCount,
                'sidebarProjectDraftCount' => $sidebarProjectDraftCount,
            ]);
        });
    }

    private function resolveSidebarProjectDraftCount(): int
    {
        $baseUrl = rtrim((string) config('services.calculation_service.base_url'), '/');
        if ($baseUrl === '') {
            return 0;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout(4)
                ->get('/api/v1/calculation-drafts', [
                    'status' => 'draft',
                ]);

            if (! $response->successful()) {
                return 0;
            }

            $data = $response->json('data', []);

            return is_array($data) ? count($data) : 0;
        } catch (Throwable) {
            return 0;
        }
    }
}
