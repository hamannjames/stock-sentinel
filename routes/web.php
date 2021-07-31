<?php

use App\Http\Helpers\EfdConnector;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

// Route to test my api while debuggin. Not used by user.
Route::get('/testapi', function(){
    $efd = new EfdConnector();
    dd($efd->ptrIndex('01/01/2020', '12/31/2020')->current());
});