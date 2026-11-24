<?php

use App\Http\Controllers\AccountingBooksController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ControlController;
use App\Http\Controllers\CurrencyExchangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryExtraController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShippingExpenseController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('transactions', TransactionController::class);
    Route::get('/cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
    Route::post('/cashbox', [CashboxController::class, 'store'])->name('cashbox.store');
    Route::delete('/cashbox/{cashMovement}', [CashboxController::class, 'destroy'])->name('cashbox.destroy');

    Route::resource('items', ItemController::class);
    Route::resource('contacts', ContactController::class);
    Route::get('/contacts/{contact}/statement', [ContactController::class, 'statement'])->name('contacts.statement');

    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/', [VoucherController::class, 'store'])->name('store');
        Route::put('/{voucher}', [VoucherController::class, 'update'])->name('update');
        Route::get('/{voucher}', [VoucherController::class, 'show'])->name('show');
    });
    Route::get('/expenses', [VoucherController::class, 'expenses'])->name('expenses.index');
    Route::post('/expenses', [VoucherController::class, 'storeExpense'])->name('expenses.store');

    Route::get('/currency-exchanges', [CurrencyExchangeController::class, 'index'])->name('currency-exchanges.index');
    Route::post('/currency-exchanges', [CurrencyExchangeController::class, 'store'])->name('currency-exchanges.store');
    Route::delete('/currency-exchanges/{currencyExchange}', [CurrencyExchangeController::class, 'destroy'])->name('currency-exchanges.destroy');

    Route::get('/shipping', [ShippingExpenseController::class, 'index'])->name('shipping.index');
    Route::post('/shipping', [ShippingExpenseController::class, 'store'])->name('shipping.store');
    Route::delete('/shipping/{shippingExpense}', [ShippingExpenseController::class, 'destroy'])->name('shipping.destroy');

    Route::get('/inventory/sheet', [InventoryExtraController::class, 'sheet'])->name('inventory.sheet');
    Route::get('/inventory/movement', [InventoryExtraController::class, 'movement'])->name('inventory.movement');
    Route::get('/inventory/alerts', [InventoryExtraController::class, 'alerts'])->name('inventory.alerts');
    Route::get('/inventory/locations', [InventoryExtraController::class, 'locations'])->name('inventory.locations');
    Route::post('/inventory/locations', [InventoryExtraController::class, 'storeLocation'])->name('inventory.locations.store');
    Route::post('/inventory/openings', [InventoryExtraController::class, 'storeOpening'])->name('inventory.openings.store');
    Route::get('/inventory/transfers', [InventoryExtraController::class, 'transfers'])->name('inventory.transfers');
    Route::post('/inventory/transfers', [InventoryExtraController::class, 'storeTransfer'])->name('inventory.transfers.store');

    Route::get('/employees', [PeopleController::class, 'employees'])->name('employees.index');
    Route::post('/employees', [PeopleController::class, 'storeEmployee'])->name('employees.store');
    Route::get('/employees/{employee}/statement', [PeopleController::class, 'employeeStatement'])->name('employees.statement');
    Route::get('/salaries', [PeopleController::class, 'salaries'])->name('salaries.index');
    Route::post('/salaries', [PeopleController::class, 'storeSalary'])->name('salaries.store');
    Route::get('/employee-movements', [PeopleController::class, 'movements'])->name('employee-movements.index');
    Route::post('/employee-movements', [PeopleController::class, 'storeMovement'])->name('employee-movements.store');
    Route::get('/partners', [PeopleController::class, 'partners'])->name('partners.index');
    Route::post('/partners', [PeopleController::class, 'storePartner'])->name('partners.store');
    Route::post('/withdrawals', [PeopleController::class, 'storeWithdrawal'])->name('withdrawals.store');

    Route::get('/books/accounts', [AccountingBooksController::class, 'accounts'])->name('books.accounts');
    Route::post('/books/accounts', [AccountingBooksController::class, 'storeAccount'])->name('books.accounts.store');
    Route::put('/books/accounts/{account}', [AccountingBooksController::class, 'updateAccount'])->name('books.accounts.update');
    Route::delete('/books/accounts/{account}', [AccountingBooksController::class, 'destroyAccount'])->name('books.accounts.destroy');
    Route::post('/books/rebuild', [AccountingBooksController::class, 'rebuildEntries'])->name('books.rebuild');
    Route::post('/books/revalue-fx', [AccountingBooksController::class, 'revalueFx'])->name('books.revalue-fx');
    Route::get('/books/mappings', [AccountingBooksController::class, 'mappings'])->name('books.mappings');
    Route::post('/books/mappings', [AccountingBooksController::class, 'saveMappings'])->name('books.mappings.save');
    Route::get('/books/ledger', [AccountingBooksController::class, 'ledger'])->name('books.ledger');
    Route::post('/books/ledger', [AccountingBooksController::class, 'storeJournal'])->name('books.ledger.store');
    Route::delete('/books/ledger/{journalEntry}', [AccountingBooksController::class, 'destroyJournal'])->name('books.ledger.destroy');
    Route::get('/books/trial', [AccountingBooksController::class, 'trial'])->name('books.trial');
    Route::get('/books/cost-centers', [AccountingBooksController::class, 'costCenters'])->name('books.cost-centers');
    Route::post('/books/cost-centers', [AccountingBooksController::class, 'storeCostCenter'])->name('books.cost-centers.store');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/suppliers', [ReportController::class, 'suppliers'])->name('suppliers');
        Route::get('/fx-differences', [ReportController::class, 'fxDifferences'])->name('fx');
        Route::get('/trading', [ReportController::class, 'trading'])->name('trading');
        Route::get('/pnl', [ReportController::class, 'pnl'])->name('pnl');
        Route::get('/balance', [ReportController::class, 'balanceSheet'])->name('balance');
        Route::get('/final-accounts', [ReportController::class, 'finalAccounts'])->name('final-accounts');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/cashflow', [ReportController::class, 'cashflow'])->name('cashflow');
        Route::get('/profitability', [ReportController::class, 'profitability'])->name('profitability');
    });

    Route::get('/control/daily', [ControlController::class, 'daily'])->name('control.daily');
    Route::get('/control/owner', [ControlController::class, 'owner'])->name('control.owner');
    Route::get('/control/kpis', [ControlController::class, 'kpis'])->name('control.kpis');
    Route::get('/control/collection', [ControlController::class, 'collection'])->name('control.collection');
    Route::get('/control/cash-close', [ControlController::class, 'cashClose'])->name('control.cash-close');
    Route::get('/control/match', [ControlController::class, 'match'])->name('control.match');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::middleware(['role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/shops', [SuperAdminController::class, 'index'])->name('shops');
        Route::post('/shops', [SuperAdminController::class, 'store'])->name('shops.store');
        Route::patch('/shops/{shop}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('shops.toggle-status');
        Route::post('/shops/{shop}/impersonate', [SuperAdminController::class, 'impersonate'])->name('shops.impersonate');
        Route::post('/leave-impersonation', [SuperAdminController::class, 'leaveImpersonation'])->name('leave-impersonation');
    });
});

require __DIR__.'/auth.php';
