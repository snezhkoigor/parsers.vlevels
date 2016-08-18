<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cme\Eur;

class ParseEur extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ParseEur';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by EUR pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $eur = new Eur();
        $eur->parse();
    }
}
