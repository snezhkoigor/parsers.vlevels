<?php

namespace App\Console\Commands;

use App\Services\Cme\Aud;
use App\Services\Cme\Base;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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

        if (($files = $aud->getFiles()) && ($option = $aud->getOption())) {
            $months = $aud->getMonths($aud->getCmeFilePath() . $files[$aud::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $aud->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Aud(strtotime("-1 DAY", $option_by_month->_expiration));
                        $other_month->update_day_table = false;

                        $other_month->parse();

                        unset($option_by_month);
                    }
                }
            }
        }
    }
}
