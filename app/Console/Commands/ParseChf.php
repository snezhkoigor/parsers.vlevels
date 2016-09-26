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

        if (($files = $chf->getFiles()) && ($option = $chf->getOption())) {
            $months = $chf->getMonths($chf->getCmeFilePath() . $files[$chf::CME_BULLETIN_TYPE_CALL], $option->_option_month);

            if (count($months) !== 0) {
                foreach ($months as $month) {
                    $option_by_month = $chf->getOptionDataByMonth($month);

                    if (!empty($option_by_month)) {
                        $other_month = new Chf($option_by_month->_expiration);

                        if ($option->_option_month != $option_by_month->_option_month) {
                            $other_month->update_day_table = false;
                            $other_month->update_fractal_field_table = false;
                        }

                        $other_month->parse();

                        unset($option_by_month);
                        unset($other_month);
                    }
                }
            }
        }
    }
}
