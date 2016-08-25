<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cme\Xau;

class ParseXau extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseXau';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by XAU pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $xau = new Xau();
        $xau->parse();
    }
}
