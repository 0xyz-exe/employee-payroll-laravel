<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Payroll Desk</h2></x-slot>
<div class="py-8"><div class="max-w-5xl mx-auto px-4 space-y-4"><p>Signed in as <strong>{{ Auth::user()->role }}</strong>.</p>
<div class="grid gap-4 sm:grid-cols-2">
@if(in_array(Auth::user()->role,['admin','hr']))<a class="rounded-xl bg-white p-6 shadow hover:ring-2 ring-indigo-500" href="{{ route('employees.index') }}"><h3 class="font-bold">Employees</h3><p>Manage employee profiles, compensation, attendance.</p></a>@endif
@if(in_array(Auth::user()->role,['admin','hr','payroll']))<a class="rounded-xl bg-white p-6 shadow hover:ring-2 ring-indigo-500" href="{{ route('payroll.index') }}"><h3 class="font-bold">Payroll runs</h3><p>Calculate, review, approve, finalize, export.</p></a>@endif
@if(Auth::user()->role==='employee')<a class="rounded-xl bg-white p-6 shadow hover:ring-2 ring-indigo-500" href="{{ route('payslips.mine') }}"><h3 class="font-bold">My payslips</h3><p>View finalized payslips linked to this account.</p></a>@endif
</div></div></div>
</x-app-layout>
