<?php

namespace App\Console\Commands;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Console\Command;

class SendWelcomeEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:send-welcome-email {user_id : The ID of the user to send the welcome email to}';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch a welcome email job for a specific user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::find($this->argument('user_id'));

        if (! $user) {
            $this->error("User with ID {$this->argument('user_id')} not found.");

            return self::FAILURE;
        }

        SendWelcomeEmail::dispatch($user);

        $this->info("Welcome email job dispatched for {$user->name} ({$user->email}).");

        return self::SUCCESS;
    }
}
