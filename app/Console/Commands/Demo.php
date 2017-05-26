<?php

namespace App\Console\Commands;

use App\Services\Cme\Euu;
use App\Services\Cme\Xau;
use Faker\Provider\DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        $xau = new Euu();

        $xau->parseMonths();
    }
}
