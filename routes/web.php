<?php

use App\Http\Helpers\EfdConnector;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TickerController;
use App\Http\Controllers\SenatorController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/senators', [SenatorController::class, 'index'])->name('senator.index');
Route::get('/senators/{senator:slug}', [SenatorController::class, 'show'])->name('senator.show');
Route::get('/tickers', [TickerController::class, 'index'])->name('ticker.index');
Route::get('/tickers/{ticker:slug}', [TickerController::class, 'show'])->name('ticker.show');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';