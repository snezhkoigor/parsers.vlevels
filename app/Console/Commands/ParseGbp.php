<?php

namespace App\Console\Commands;

use App\Services\Cme\Gbp;
use Illuminate\Console\Command;

class ParseGbp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ParseGbp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by GBP pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $gbp = new Gbp();
        $gbp->parse();
    }
}
