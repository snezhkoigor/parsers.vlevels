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

        if (($files = $aud->getFiles()) && ($option = $aud->getOption())) {
            $months = $aud->getMonths($aud->getCmeFilePath() . $files[$aud::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $aud->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Aud($option_by_month->_expiration);

                        if ($option->_option_month != $option_by_month->_option_month) {
                            $other_month->update_day_table = false;
                        }
                        
                        $other_month->parse();

                        unset($option_by_month);
                    }
                }
            }
        }
    }
}
