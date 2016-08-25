<?php

namespace App\Console\Commands;

use App\Services\Cme\Jpy;
use Illuminate\Console\Command;

class ParseJpy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseJpy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by JPY pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $jpy = new Jpy();
        $jpy->parse();
    }
}
