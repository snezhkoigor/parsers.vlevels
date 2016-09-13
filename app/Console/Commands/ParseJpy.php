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

        if (($files = $jpy->getFiles()) && ($option = $jpy->getOption())) {
            $months = $jpy->getMonths($jpy->getCmeFilePath() . $files[$jpy::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $jpy->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Jpy(strtotime("-1 DAY", $option_by_month->_expiration));
                        $other_month->update_day_table = false;

                        $other_month->parse();

                        unset($option_by_month);
                    }
                }
            }
        }
    }
}
