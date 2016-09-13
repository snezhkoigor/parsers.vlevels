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
    protected $signature = 'parseChf';

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

        if (($files = $chf->getFiles()) && ($option = $chf->getOption())) {
            $months = $chf->getMonths($chf->getCmeFilePath() . $files[$chf::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $chf->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Chf(strtotime("-1 DAY", $option_by_month->_expiration));
                        $other_month->update_day_table = false;

                        $other_month->parse();

                        unset($option_by_month);
                    }

                }
            }
        }
    }
}
