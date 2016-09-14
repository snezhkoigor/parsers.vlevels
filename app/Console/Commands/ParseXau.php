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

        if (($files = $xau->getFiles()) && ($option = $xau->getOption())) {
            $months = $xau->getMonths($xau->getCmeFilePath() . $files[$xau::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $xau->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Xau(strtotime("-1 DAY", $option_by_month->_expiration));
                        $other_month->update_day_table = false;

                        $other_month->parse();

                        unset($option_by_month);
                    }
                }
            }
        }
    }
}
