<?php

namespace App\Console;

use App\Models\Ptr;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrackingTransactionMailable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            echo 'looking for transactions';
            $ptrs = Ptr::whereDate('created_at', Carbon::yesterday())->get();

            if ($ptrs->isNotEmpty()) {
                $ptrs->each(function($ptr){
                    $transactions = Transaction::where('ptr_id', $ptr->uuid)->with('transactor')->with('ticker')->with('transactionType')->get();

                    $transactions->each(function($transaction){

                        $connections = $transaction->ticker
                            ->connections
                            ->concat($transaction->transactor->connections)
                            ->pluck('user_id')
                            ->unique();

                        $connections->each(function($connection) use ($transaction){
                            $user = User::find($connection);

                            Mail::to($user)
                                ->queue(new TrackingTransactionMailable($transaction));
                        });
                    });
                });
            }
            echo ' done';
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
