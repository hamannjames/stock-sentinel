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

/*
    Like last quarter, I am gonna use this file to talk about the way to navigate this app. Start here,
    with all the routes I created. You will notice that I have no routes for authentication here. That is because those were created by a package I installed called breeze. You can see those in the git repo and I commented them to show I know what they are doing. It is pretty much the same way I handled auth last quarter, but it came with nice pre-built layouts

    Every route has a controller class. Controller classes can be found in the app/Http/Controllers folder.

    Every controller will either return a view, found in resources/views folder. Views sometimes call other
    views, since they are all just template files using the blade engine. If you see a view using this
    syntax: "<x-{name of component} />" you are seeing the view call another view component and passing
    it data. Almost all of the time that is calling a component in /resources/views/components folder, so
    follow it there. you might see something like <x-component.other-name />, which just means it is
    in a sub-directory.

    Sometimes controllers also use models to perform queries. All models can be found in app/Models.
    Models map to database tables and perform queries, among other things.

    Beyond controllers views and models I also have a lot of other stuff written in this app. Go to app/Http/Helpers
    to see all my classes for connecting to apis and processing data in the backend. Go to app/Console/Commands to see all the classes I uses to generate command line commands to seed data from my APIs, among
    some other things.

    Go to app/Console/Kernel to see the task I wrote which is basically a cron task to send emails when
    a transaction is made someone is following. Testing instructions are in that file.

    Go to tests folder to see tests I wrote for my connectors and processors for api data. I wrote a lot.
    You can run all of them with "php artisan test" but it will kill your computer. I ran them one at a
    time with a VSCode PHPUnit extension. They might not all pass now since the connector and processor
    classes changed a lot up until a few weeks before the due date.
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/senators', [SenatorController::class, 'index'])->name('senator.index');
Route::get('/senators/{senator:slug}', [SenatorController::class, 'show'])->name('senator.show');
Route::get('/tickers', [TickerController::class, 'index'])->name('ticker.index');
Route::get('/tickers/{ticker:slug}', [TickerController::class, 'show'])->name('ticker.show');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';