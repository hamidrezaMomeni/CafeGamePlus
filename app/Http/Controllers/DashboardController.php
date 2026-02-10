<?php

namespace App\Http\Controllers;

use App\Models\Console;
use App\Models\ConsoleSession;
use App\Models\BoardGame;
use App\Models\BoardGameSession;
use App\Models\CafeItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PricingPlan;
use App\Models\User;
use App\Models\Table;
use App\Models\TableSession;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_consoles' => Console::count(),
            'available_consoles' => Console::where('status', 'available')->count(),
            'busy_consoles' => Console::where('status', 'busy')->count(),
            'total_tables' => Table::count(),
            'available_tables' => Table::where('status', 'available')->count(),
            'busy_tables' => Table::where('status', 'busy')->count(),
            'total_board_games' => BoardGame::count(),
            'available_board_games' => BoardGame::where('status', 'available')->count(),
            'busy_board_games' => BoardGame::where('status', 'busy')->count(),
            'total_customers' => Customer::count(),
            'today_revenue' => Invoice::whereDate('created_at', Carbon::today())
                ->where('status', 'paid')
                ->sum('total_amount'),
            'active_console_sessions' => ConsoleSession::where('status', 'active')->count(),
            'active_table_sessions' => TableSession::where('status', 'active')->count(),
            'active_board_game_sessions' => BoardGameSession::where('status', 'active')->count(),
        ];

        $consoles = Console::query()->latest()->paginate(10, ['*'], 'consoles_page');
        $tables = Table::query()->latest()->paginate(10, ['*'], 'tables_page');
        $boardGames = BoardGame::query()->latest()->paginate(10, ['*'], 'board_games_page');
        $customers = Customer::withCount('invoices')->latest()->paginate(10, ['*'], 'customers_page');
        $cafeItems = CafeItem::query()->latest()->paginate(10, ['*'], 'cafe_items_page');
        $orders = Order::with(['items.cafeItem', 'customer', 'table', 'invoice'])
            ->latest()
            ->paginate(10, ['*'], 'orders_page');
        $invoices = Invoice::with([
            'customer',
            'consoleSessions.console',
            'tableSessions.table',
            'boardGameSessions.boardGame',
            'orders.items.cafeItem',
            'orders.table',
        ])
            ->latest()
            ->paginate(10, ['*'], 'invoices_page');
        $users = User::query()->latest()->paginate(10, ['*'], 'users_page');
        $pricingPlans = PricingPlan::query()
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->latest()
            ->paginate(10, ['*'], 'pricing_plans_page');
        $pricingPlanTypes = PricingPlan::availableTypes();
        $pricingPlanAppliesTo = PricingPlan::availableAppliesTo();
        $permissions = User::availablePermissions();
        $consolesAll = Console::all();
        $tablesAll = Table::all();
        $boardGamesAll = BoardGame::all();
        $customersAll = Customer::all();
        $cafeItemsAll = CafeItem::all();
        $pendingOrdersByTable = Order::with('invoice')
            ->where('status', 'pending')
            ->whereNotNull('table_id')
            ->get()
            ->groupBy('table_id');
        $activeSessions = ConsoleSession::with(['console', 'customer', 'invoice'])
            ->where('status', 'active')
            ->latest()
            ->get();
        $activeTableSessions = TableSession::with(['table', 'customer', 'invoice'])
            ->where('status', 'active')
            ->latest()
            ->get();
        $activeBoardGameSessions = BoardGameSession::with(['boardGame', 'customer', 'invoice'])
            ->where('status', 'active')
            ->latest()
            ->get();

        $consoleSessions = ConsoleSession::with(['console', 'customer'])
            ->latest()
            ->paginate(10, ['*'], 'console_sessions_page');
        $tableSessions = TableSession::with(['table', 'customer'])
            ->latest()
            ->paginate(10, ['*'], 'table_sessions_page');
        $boardGameSessions = BoardGameSession::with(['boardGame', 'customer'])
            ->latest()
            ->paginate(10, ['*'], 'board_game_sessions_page');

        return view('dashboard', compact(
            'stats',
            'consoles',
            'tables',
            'boardGames',
            'customers',
            'cafeItems',
            'orders',
            'invoices',
            'users',
            'pricingPlans',
            'pricingPlanTypes',
            'pricingPlanAppliesTo',
            'permissions',
            'consolesAll',
            'tablesAll',
            'boardGamesAll',
            'customersAll',
            'cafeItemsAll',
            'pendingOrdersByTable',
            'activeSessions',
            'activeTableSessions',
            'activeBoardGameSessions',
            'consoleSessions',
            'tableSessions',
            'boardGameSessions'
        ));
    }
}
