<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Demo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demo Scheduler';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Test ' . date('Y-m-d'));
    }
}
