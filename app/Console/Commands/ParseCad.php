<?php

namespace App\Console\Commands;

use App\Services\Cme\Cad;
use Illuminate\Console\Command;

class ParseCad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ParseCad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by CAD pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cad = new Cad();
        $cad->parse();
    }
}
