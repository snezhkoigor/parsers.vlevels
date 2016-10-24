<?php

namespace App\Console\Commands;

use App\Services\Cme\Base;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Services\Cme\Aud;
use App\Services\Cme\Cad;
use App\Services\Cme\Jpy;
use App\Services\Cme\Chf;
use App\Services\Cme\Gbp;
use App\Services\Cme\Eur;
use App\Services\Cme\Xau;

use DB;

class GetDataFromHTTP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getDataFromHTTP {instrument* : without USD (ex: aud)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get json data from CME http website';

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
        $disk = Storage::disk(Base::$storage);
        $instruments = $this->argument('instrument');

        foreach ($instruments as $instrument) {
            $instrument = strtoupper($instrument);

            $pair_obj = null;
            if (!empty($instrument)) {
                $pair_obj = null;

                switch ($instrument) {
                    case Base::PAIR_AUD:
                        $pair_obj = new Aud();

                        break;

                    case Base::PAIR_CAD:
                        $pair_obj = new Cad();

                        break;

                    case Base::PAIR_CHF:
                        $pair_obj = new Chf();

                        break;

                    case Base::PAIR_EUR:
                        $pair_obj = new Eur();

                        break;

                    case Base::PAIR_GBP:
                        $pair_obj = new Gbp();

                        break;

                    case Base::PAIR_JPY:
                        $pair_obj = new Jpy();

                        break;

                    case Base::PAIR_XAU:
                        $pair_obj = new Xau();

                        break;
                }
            }

            $folder = env('CME_PARSER_JSON_SAVE_FOLDER') . '/' . date("Y") . '/' . date('Ymd', $pair_obj->pdf_files_date) . '/' . $pair_obj->pair . '/';
            if (Base::isFolderIsNotEmpty($folder)) {
                Log::info('Файл уже скопирован.', ['folder' => $folder, 'pair' => $pair_obj->pair]);
            } else {
                $data = $pair_obj->getDataFromJson();

                if (count($data) !== 0) {
                    //Usage
                    $disk->put($folder . env('CME_JSON_FILE_NAME'), json_encode($data));
                    Log::info('Файл создан.', ['folder' => $folder, 'pair' => $pair_obj->pair, 'data' => json_encode($data)]);
                } else {
                    Log::warning('Нет данных с сайта CME или плохо спарсили.', ['folder' => $folder, 'pair' => $pair_obj->pair, 'data' => json_encode($data)]);
                }
            }
        }
    }
}