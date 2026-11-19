<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Employee;
use App\Models\EmployeeMovement;
use App\Models\Partner;
use App\Models\PartnerWithdrawal;
use App\Models\SalaryRecord;
use App\Services\AccountingService;
use App\Services\DocumentPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeopleController extends Controller
{
    public function __construct(
        protected DocumentPostingService $posting,
        protected AccountingService $accounting,
    ) {}

    public function employees()
    {
        return view('people.employees', [
            'employees' => Employee::orderBy('name')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function storeEmployee(Request $request)
    {
        Employee::create($request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم حفظ الموظف.');
    }

    public function salaries()
    {
        return view('people.salaries', [
            'rows' => SalaryRecord::with('employee')->latest('date')->get(),
            'employees' => Employee::orderBy('name')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function storeSalary(Request $request)
    {
        $this->posting->saveSalary($request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'voucher_number' => 'nullable|string',
            'month' => 'required|string',
            'basic' => 'required|numeric|min:0',
            'extra' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم تسجيل الراتب وأثر الصندوق.');
    }

    public function movements()
    {
        return view('people.movements', [
            'rows' => EmployeeMovement::with('employee')->latest('date')->get(),
            'employees' => Employee::orderBy('name')->get(),
            'shop' => current_shop(),
        ]);
    }

    public function storeMovement(Request $request)
    {
        $this->posting->saveEmployeeMovement($request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'voucher_number' => 'nullable|string',
            'type' => 'required|in:advance,salary_payment,settlement',
            'month' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم تسجيل حركة الموظف.');
    }

    public function employeeStatement(Employee $employee)
    {
        $salaries = $employee->salaries()->get();
        $movements = $employee->movements()->get();
        $due = (float) $salaries->sum('amount_usd');
        $salaryPaid = (float) $movements->where('type', 'salary_payment')->sum('amount_usd');
        $advances = (float) $movements->where('type', 'advance')->sum('amount_usd');
        return view('people.employee-statement', compact('employee', 'salaries', 'movements', 'due', 'salaryPaid', 'advances') + [
            'shop' => current_shop(),
        ]);
    }

    public function partners()
    {
        $rows = PartnerWithdrawal::with('partner')->latest('date')->get();
        $linkedIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $linkedCashIds = $rows->pluck('cash_movement_id')->filter()->map(fn ($id) => (int) $id)->all();

        $manualWithdrawals = CashMovement::with('contact')
            ->where('type', 'withdrawal')
            ->latest('date')
            ->get()
            ->reject(fn ($m) => ($m->id && in_array((int) $m->id, $linkedCashIds, true))
                || ($m->source_type === 'withdrawal' && $m->source_id && in_array((int) $m->source_id, $linkedIds, true)))
            ->values();

        return view('people.withdrawals', [
            'partners' => Partner::orderBy('name')->get(),
            'rows' => $rows,
            'manualWithdrawals' => $manualWithdrawals,
            'shop' => current_shop(),
        ]);
    }

    public function storePartner(Request $request)
    {
        Partner::create($request->validate([
            'name' => 'required|string',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم حفظ الشريك.');
    }

    public function storeWithdrawal(Request $request)
    {
        $this->posting->saveWithdrawal($request->validate([
            'partner_id' => 'required|exists:partners,id',
            'date' => 'required|date',
            'voucher_number' => 'nullable|string',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,SYP',
            'exchange_rate' => 'required|numeric|min:1',
            'method' => 'nullable|string',
            'nature' => 'required|string',
            'classification_status' => 'required|string',
            'notes' => 'nullable|string',
        ]));
        return back()->with('success', 'تم تسجيل المسحوب.');
    }
}
