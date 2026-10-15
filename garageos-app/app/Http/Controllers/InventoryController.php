<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\ManageInventoryStock;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\InventoryTransaction;
use App\Models\Part;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        Gate::forUser($user)->authorize('viewAny', Part::class);
        $organizationId = $user->organization_id;
        $search = trim((string) request()->query('q', ''));
        $lowStockOnly = request()->boolean('low_stock');
        $sortable = [
            'name' => 'name',
            'sku' => 'sku',
            'available_stock' => '(stock - reserved_stock)',
            'min_stock' => 'min_stock',
            'cost_price' => 'cost_price',
            'sale_price' => 'sale_price',
        ];
        $sort = (string) request()->query('sort', 'name');
        $sort = array_key_exists($sort, $sortable) ? $sort : 'name';
        $direction = request()->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = (int) request()->query('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $parts = Part::query()
            ->where('organization_id', $organizationId)
            ->where('active', true)
            ->select('*')
            ->selectRaw('(stock - reserved_stock) as available_stock')
            ->selectRaw('(stock - reserved_stock) <= min_stock as is_low_stock')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('supplier', 'like', $like);
            }))
            ->when($lowStockOnly, fn ($query) => $query->whereRaw('(stock - reserved_stock) <= min_stock'));

        if (request()->query('sort') && array_key_exists($sort, $sortable)) {
            $parts->orderByRaw($sortable[$sort].' '.$direction);
        } else {
            $parts->orderByRaw('CASE WHEN (stock - reserved_stock) <= min_stock THEN 0 ELSE 1 END')
                ->orderBy('name');
        }

        return Inertia::render('Inventory/Index', [
            'parts' => $parts->paginate($perPage)->withQueryString(),
            'filters' => [
                'q' => $search,
                'lowStock' => $lowStockOnly,
                'sort' => $sort,
                'direction' => $direction,
                'perPage' => $perPage,
            ],
            'summary' => [
                'parts' => Part::where('organization_id', $organizationId)->where('active', true)->count(),
                'lowStock' => Part::where('organization_id', $organizationId)->where('active', true)->whereRaw('(stock - reserved_stock) <= min_stock')->count(),
                'stockValue' => (int) Part::where('organization_id', $organizationId)->where('active', true)->selectRaw('COALESCE(SUM(stock * cost_price), 0) as value')->value('value'),
            ],
            'transactions' => InventoryTransaction::query()
                ->where('organization_id', $organizationId)
                ->with(['part:id,name,unit', 'user:id,name'])
                ->latest()
                ->limit(12)
                ->get()
                ->map(fn (InventoryTransaction $transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'quantity' => (float) $transaction->quantity,
                    'part' => $transaction->part->name,
                    'unit' => $transaction->part->unit,
                    'user' => $transaction->user?->name,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at,
                ])
                ->values(),
        ]);
    }

    public function store(StorePartRequest $request, ManageInventoryStock $manageInventoryStock): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', Part::class);
        $data = $request->validated();
        $initialStock = (float) ($data['initial_stock'] ?? 0);

        $part = DB::transaction(function () use ($request, $data, $initialStock, $manageInventoryStock): Part {
            $part = Part::create([
                'organization_id' => $request->user()->organization_id,
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'stock' => 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'unit' => $data['unit'],
                'active' => true,
            ]);

            if ($initialStock > 0) {
                $manageInventoryStock->handle($request->user(), $part, 'receive', $initialStock, 'Начальный остаток');
            }

            Activity::record('inventory.part_created', $part, $request->user(), ['sku' => $part->sku, 'initial_stock' => $initialStock]);

            return $part;
        });

        return to_route('inventory.index')->with('success', "Запчасть {$part->name} добавлена на склад.");
    }

    public function moveStock(StoreStockMovementRequest $request, Part $part, ManageInventoryStock $manageInventoryStock): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $part);
        $data = $request->validated();
        $quantity = (float) $data['quantity'];
        if ($data['type'] !== 'adjustment' && $quantity <= 0) {
            return back()->withErrors(['quantity' => 'Укажите количество больше нуля.']);
        }

        $manageInventoryStock->handle(
            $request->user(),
            $part,
            $data['type'],
            $quantity,
            $data['note'] ?? null,
            'manual',
            null,
        );

        return to_route('inventory.index')->with('success', 'Остаток и журнал склада обновлены.');
    }
}
