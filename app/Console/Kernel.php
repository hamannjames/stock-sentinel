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

// This class is soley used for the task of sending emails every day if a transactor or ticker
// someone follows makes a transaction
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
        // I pull any ptrs from the day before, since this runs at midnight. If they exist, I get all
        // transactions from that ptr, then check if the transaction's senator or ticker is being
        // followed by any user. If so, I send an email to the user with the transaction details

        // To test this function, replace the line $ptrs = Ptr::whereDate('created_at', Carbon::yesterday())->get(); with
        // $ptrs = Ptr::where('uuid', '045f76aa-7ae2-4040-a603-67b5ebc3b271')->get(); This will call a
        // specific PTR. Make sure your user has a connection to the stock ticker "C" for CitiGroup.
        // Next, in the .env file change the MAIL_MAILER setting to "log."
        // Next, run the command line command "php artisan schedule:run"
        // you can check the laravel.log file in storage/logs for the email output
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
        })->everyDay();
        // called every day like a cron task
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
