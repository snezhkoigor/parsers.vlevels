<?php

namespace App\Console\Commands;

use App\Services\Cme\Base;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Services\Cme\Xau;
use App\Services\Cme\EsEom;
use App\Services\Cme\SpEom;
use App\Services\Cme\Lo;
use App\Services\Cme\Euu;
use App\Services\Cme\Jpu;
use App\Services\Cme\Gbu;
use App\Services\Cme\Adu;
use App\Services\Cme\Cau;
use App\Services\Cme\Chu;

use DB;

class GetCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetCalendar {instrument* : without USD (ex: aud)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get calendar from CME http website';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $instruments = $this->argument('instrument');

        foreach ($instruments as $instrument) {
            $instrument = strtoupper($instrument);

            $pair_obj = null;
            if (!empty($instrument)) {
                $pair_obj = null;

                switch ($instrument) {
                    case Base::PAIR_XAU:
                        $pair_obj = new Xau();

                        break;

                    case Base::PAIR_SP500_EOM:
                        $pair_obj = new SpEom();

                        break;

                    case Base::PAIR_ESEOM:
                        $pair_obj = new EsEom();

                        break;

                    case Base::PAIR_LO:
                        $pair_obj = new Lo();

                        break;

                    case Base::PAIR_EUU:
                        $pair_obj = new Euu();

                        break;

                    case Base::PAIR_JPU:
                        $pair_obj = new Jpu();

                        break;

                    case Base::PAIR_GBU:
                        $pair_obj = new Gbu();

                        break;

                    case Base::PAIR_ADU:
                        $pair_obj = new Adu();

                        break;

                    case Base::PAIR_CAU:
                        $pair_obj = new Cau();

                        break;

                    case Base::PAIR_CHU:
                        $pair_obj = new Chu();

                        break;
                }
            }

            if ($pair_obj) {
                $pair_obj->parseMonths();
            }
        }
    }
}
