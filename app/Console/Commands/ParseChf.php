<?php

namespace App\Console\Commands;

use App\Services\Cme\Chf;
use Illuminate\Console\Command;

class ParseChf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ParseChf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by CHF pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $chf = new Chf();
        $chf->parse();
    }
}
