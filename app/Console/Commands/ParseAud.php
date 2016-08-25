<?php

namespace App\Console\Commands;

use App\Services\Cme\Aud;
use Illuminate\Console\Command;

class ParseAud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseAud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse information from *.pdf file by AUD pair';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $aud = new Aud();
        $aud->parse();
    }
}
