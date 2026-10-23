<?php

use App\Http\Controllers\PayrollEmployeeController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayrollStructureController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('payroll.home'));
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('payroll.home'))->name('dashboard');
    Route::get('/home', fn () => view('payroll.home'))->name('payroll.home');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::middleware('payroll.role:admin,hr')->group(function () {
        Route::post('/departments', [PayrollStructureController::class,'department'])->name('departments.store');
        Route::post('/positions', [PayrollStructureController::class,'position'])->name('positions.store');
        Route::resource('employees', PayrollEmployeeController::class)->except(['show','destroy']);
        Route::post('/employees/{employee}/components', [PayrollEmployeeController::class,'component'])->name('employees.components.store');
        Route::post('/employees/{employee}/attendance', [PayrollEmployeeController::class,'attendance'])->name('employees.attendance.store');
    });
    Route::middleware('payroll.role:admin,hr,payroll')->prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [PayrollRunController::class,'index'])->name('index');
        Route::post('/', [PayrollRunController::class,'store'])->name('store');
        Route::get('/{period}', [PayrollRunController::class,'show'])->name('show');
        Route::post('/{period}/recalculate', [PayrollRunController::class,'recalculate'])->name('recalculate');
        Route::post('/{period}/approve', [PayrollRunController::class,'approve'])->middleware('payroll.role:admin,payroll')->name('approve');
        Route::post('/{period}/finalize', [PayrollRunController::class,'finalize'])->middleware('payroll.role:admin,payroll')->name('finalize');
        Route::get('/{period}/export', [PayrollRunController::class,'export'])->middleware('payroll.role:admin,hr,payroll')->name('export');
        Route::get('/{period}/entries/{entry}/payslip', [PayrollRunController::class,'payslip'])->name('payslip');
    });
    Route::get('/my-payslips', [PayrollRunController::class,'mine'])->name('payslips.mine');
    Route::get('/my-payslips/{period}/entries/{entry}', [PayrollRunController::class,'payslip'])->name('payslips.show');
});
require __DIR__.'/auth.php';
