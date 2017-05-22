<?php

namespace App\Console\Commands;

use App\Services\Cme\Adu;
use App\Services\Cme\Cau;
use App\Services\Cme\Chu;
use App\Services\Cme\EsEom;
use App\Services\Cme\Euu;
use App\Services\Cme\Gbu;
use App\Services\Cme\Jpu;
use App\Services\Cme\Lo;
use App\Services\Cme\SpEom;
use App\Services\Cme\Xau;
use Illuminate\Console\Command;

class ParseAvg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseAvg';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse avg volume and coi by pairs for previous month';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        $aud = new Adu();
//        $aud->saveAvg();
//
//        $eur = new Euu();
//        $eur->saveAvg();
//
//        $gbp = new Gbu();
//        $gbp->saveAvg();
//
//        $jpy = new Jpu();
//        $jpy->saveAvg();
//
//        $cad = new Cau();
//        $cad->saveAvg();
//
//        $chf = new Chu();
//        $chf->saveAvg();
//
//        $xau = new Xau();
//        $xau->saveAvg();

        $lo = new Lo();
        $lo->saveAvg();

//        $esEom = new EsEom();
//        $esEom->saveAvg();
//
//        $spEom = new SpEom();
//        $spEom->saveAvg();
    }
}
