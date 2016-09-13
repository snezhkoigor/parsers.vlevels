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
    protected $signature = 'parseCad';

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

        if (($files = $cad->getFiles()) && ($option = $cad->getOption())) {
            $months = $cad->getMonths($cad->getCmeFilePath() . $files[$cad::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $cad->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Cad(strtotime("-1 DAY", $option_by_month->_expiration));
                        $other_month->update_day_table = false;

                        $other_month->parse();

                        unset($option_by_month);
                    }

                }
            }
        }
    }
}
