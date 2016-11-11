<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:14
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Ixudra\Curl\Facades\Curl;

class Base
{
    const PARSER_TYPE_PDF = 'pdf';
    const PARSER_TYPE_JSON = 'json';

    const CME_BULLETIN_TYPE_CALL = 'call';
    const CME_BULLETIN_TYPE_PUT = 'put';

    const CME_DB_BULLETIN_TYPE_CALL = 0;
    const CME_DB_BULLETIN_TYPE_PUT = 1;

    const PAIR_EUR = 'EUR';
    const PAIR_GBP = 'GBP';
    const PAIR_JPY = 'JPY';
    const PAIR_CHF = 'CHF';
    const PAIR_AUD = 'AUD';
    const PAIR_CAD = 'CAD';
    const PAIR_XAU = 'XAU';
    const PAIR_USD = 'USD';

    public static $storage = 'public';

    public $start_index_call = null;
    public $end_index_call = null;
    public $start_index_put = null;
    public $end_index_put = null;
    public $new_page_key_call = null;
    public $new_page_key_put = null;
    public $month_start = null;
    public $month_end = null;
    public $update_day_table = true;

    public $cme_file_path;
    public $pdf_files_date;
    public $pair;
    
    public $json_strategy = 'DEFAULT';
    public $json_option_product_id = null;
    public $json_pair_name = null;
    public $json_settle_strike_divide = 1;
    public $json_settle_multiply = 1;
    public $json_max_month_to_parse = 10000;
    // {bulletin_date} = xxxxxxxx
    public $json_main_data_link = 'http://www.cmegroup.com/CmeWS/mvc/Volume/Details/O/{option_product_id}/{bulletin_date}/P?optionProductId={option_product_id}&pageSize=500';
    // {bulletin_date} = xx/xx/xxxx
    // {year} = xx
    // {month} == $json_month_associations
    // {pair} == $json_pair_name
    // {strategy} == $json_strategy
    public $json_other_data_link = 'http://www.cmegroup.com/CmeWS/mvc/Settlements/Options/Settlements/{option_product_id}/OOF?monthYear={pair}{month}{year}&strategy={strategy}&tradeDate={bulletin_date}&pageSize=500';

    public static $email = 'i.s.sergeevich@yandex.ru';

    public $min_fractal_volume = 10;
    public $update_fractal_field_table = true;
    
    public static $month_associations = array(
        'jan' => '01',
        'feb' => '02',
        'mar' => '03',
        'apr' => '04',
        'may' => '05',
        'jun' => '06',
        'jul' => '07',
        'aug' => '08',
        'sep' => '09',
        'oct' => '10',
        'nov' => '11',
        'dec' => '12'
    );

    public static $json_month_associations = array(
        'jan' => 'F',
        'feb' => 'G',
        'mar' => 'H',
        'apr' => 'J',
        'may' => 'K',
        'jun' => 'M',
        'jul' => 'N',
        'aug' => 'Q',
        'sep' => 'U',
        'oct' => 'V',
        'nov' => 'X',
        'dec' => 'Z'
    );

    protected $files;
    protected $pair_with_major;
    protected $option;
    protected $option_date;
    protected $table;
    protected $table_day;
    protected $table_total;
    protected $table_month;
    protected $table_parser_settings = 'parser_settings';

    public function __construct($option_date = null, $pdf_files_date = null) {
        if (empty($option_date)) {
            $this->option_date = strtotime(date("d-m-Y", time()));
//            // если понедельник, то берем пятницу прошлую
//            if (date('w') == 1) {
//                $this->option_date = strtotime(date("d-m-Y", (time() - 86400*3)));
//            } else {
//                $this->option_date = strtotime(date("d-m-Y", (time() - 86400)));
//            }
        } else {
            $this->option_date = (int)$option_date ? $option_date : strtotime($option_date);
        }

        $this->files = $this->getFilesAssociations($this->pair);
        $this->table = 'cme_options';

        if (empty($pdf_files_date)) {
            // если понедельник, то берем пятницу прошлую
            if (date('w') == 1) {
                $this->pdf_files_date = strtotime(date("d-m-Y", (time() - 86400 * 3)));
            } else {
                $this->pdf_files_date = strtotime(date("d-m-Y", (time() - 86400)));
            }
        } else {
            $this->pdf_files_date = (int)$pdf_files_date ? $pdf_files_date : strtotime($pdf_files_date);
        }

        if (!Schema::hasTable($this->table_parser_settings)) {
            $this->createParserSettingsTable();
        }
    }

    public static function isFolderIsNotEmpty($folder)
    {
        $cnt = count(Storage::disk(self::$storage)->files($folder));

        return ($cnt > 0) ? true : false;
    }

    public function getOptionDataByMonth($month)
    {
        return DB::table($this->table)
            ->where(
                [
                    ['_option_month', '=', $month],
                    ['_symbol', '=', $this->pair_with_major]
                ]
            )
            ->orderBy('_expiration')
            ->first();
    }
    
    public function getFiles()
    {
        return $this->files;
    }

    public function getCmeFilePath()
    {
        return $this->cme_file_path;
    }

    public function getOption()
    {
        return $this->option;
    }
    
    public function getFilesAssociations($pair)
    {
        $result = null;

        switch (strtoupper($pair)) {
            case self::PAIR_EUR:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section39_Euro_FX_And_Cme$Index_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section39_Euro_FX_And_Cme$Index_Options.pdf'
                ];
                break;

            case self::PAIR_GBP:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section27_British_Pound_Call_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section28_British_Pound_Put_Options.pdf'
                ];

                break;

            case self::PAIR_JPY:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section33_Japanese_Yen_Call_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section34_Japanese_Yen_Put_Options.pdf'
                ];

                break;

            case self::PAIR_AUD:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section38_Australian_Dollar_New_Zealand_Dollar_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section38_Australian_Dollar_New_Zealand_Dollar_Options.pdf'
                ];

                break;

            case self::PAIR_CAD:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section29_Canadian_Dollar_Call_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section30_Canadian_Dollar_Put_Options.pdf'
                ];

                break;

            case self::PAIR_CHF:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section35_Swiss_Franc_Call_Options.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section36_Swiss_Franc_Put_Options.pdf'
                ];

                break;

            case self::PAIR_XAU:
                $result = [
                    self::CME_BULLETIN_TYPE_CALL => 'Section64_Metals_Option_Products.pdf',
                    self::CME_BULLETIN_TYPE_PUT => 'Section64_Metals_Option_Products.pdf'
                ];
                break;
        }

        return $result;
    }

    public function getTotalPairRows($data_from_pdf)
    {
        $result = array(
            'oi' => 0,
            'volume' => 0,
            'coi' => 0,
        );

        if (count($data_from_pdf) !== 0) {
            foreach ($data_from_pdf as $key => $item) {
                $result['oi'] += $item['oi'];
                $result['volume'] += $item['volume'];
                $result['coi'] += $item['coi'];
            }
        }

        return $result;
    }

    public function addTotalCmeData($id, $date, $data_call, $data_put)
    {
        $total_call = $this->getTotalPairRows($data_call);
        $total_put = $this->getTotalPairRows($data_put);

        $total = array(
            '_total_oi_call' => !empty($total_call['oi']) ? $total_call['oi'] : 0,
            '_total_volume_call' => !empty($total_call['volume']) ? $total_call['volume'] : 0,
            '_change_oi_call' => !empty($total_call['coi']) ? $total_call['coi'] : 0,
            '_total_oi_put' => !empty($total_put['oi']) ? $total_put['oi'] : 0,
            '_total_volume_put' => !empty($total_put['volume']) ? $total_put['volume'] : 0,
            '_change_oi_put' =>  !empty($total_put['coi']) ? $total_put['coi'] : 0,
        );

        if (count($total) !== 0) {
            if (!Schema::hasTable($this->table_total)) {
                $this->createTotalTable();
            }

            if (($info = DB::table($this->table_total)->where([ ['_date', '=', $date], ['_option', '=', $id] ])->first())) {
                Log::info('Изменение суммарных данных.', [ 'table' => $this->table_total, 'data' => json_encode($total), 'id' => $info->_id, 'option_id' => $id, 'date' => $date, 'put' => json_encode($data_put), 'call' => json_encode($data_call) ]);

                DB::table($this->table_total)
                    ->where('_id', $info->_id)
                    ->update(
                        [
                            '_total_oi_call' => $total['_total_oi_call'],
                            '_total_volume_call' => $total['_total_volume_call'],
                            '_change_oi_call' => $total['_change_oi_call'],
                            '_total_oi_put' => $total['_total_oi_put'],
                            '_total_volume_put' => $total['_total_volume_put'],
                            '_change_oi_put' => $total['_change_oi_put']
                        ]
                    );
            } else {
                Log::info('Добавление суммарных данных.', [ 'table' => $this->table_total, 'data' => json_encode($total), 'option_id' => $id, 'date' => $date, 'put' => json_encode($data_put), 'call' => json_encode($data_call) ]);

                DB::table($this->table_total)
                    ->insert(
                        [
                            '_option' => $id,
                            '_date' => $date,
                            '_total_oi_call' => $total['_total_oi_call'],
                            '_total_volume_call' => $total['_total_volume_call'],
                            '_change_oi_call' => $total['_change_oi_call'],
                            '_total_oi_put' => $total['_total_oi_put'],
                            '_total_volume_put' => $total['_total_volume_put'],
                            '_change_oi_put' => $total['_change_oi_put'],
                        ]
                    );
            }
        } else {
            Log::warning('Нет суммарных данных.', [ 'table' => $this->table_total, 'data' => json_encode($total), 'option_id' => $id, 'date' => $date, 'put' => json_encode($data_put), 'call' => json_encode($data_call) ]);
        }
    }

    public function deleteCmeData($date, $type, $option)
    {
        DB::table($this->table_month)
            ->where(
                [
                    ['_date', '=', $date],
                    ['_type', '=', ($type == self::CME_BULLETIN_TYPE_CALL ? self::CME_DB_BULLETIN_TYPE_CALL : self::CME_DB_BULLETIN_TYPE_PUT)],
                    ['_option', '=', $option]
                ]
            )
            ->delete();
    }

    public function addCmeData($date, $data, $type)
    {
        $max_oi = 0;

        if (count($data) !== 0) {
            $data_for_insert = array();

            if (!Schema::hasTable($this->table_month)) {
                $this->createMonthTable();
            }

            $this->deleteCmeData($date, $type, $this->option->_id);

            foreach ($data as $key => $item) {
                if ((int)$item['oi'] > $max_oi) {
                    $max_oi = (int)$item['oi'];
                }

                $data_for_insert[] = array(
                    '_option' => $this->option->_id,
                    '_date' => $date,
                    '_type' => ($type == self::CME_BULLETIN_TYPE_CALL ? self::CME_DB_BULLETIN_TYPE_CALL : self::CME_DB_BULLETIN_TYPE_PUT),
                    '_strike' => $item['strike'],
                    '_reciprocal' => $item['reciprocal'],
                    '_volume' => $item['volume'],
                    '_oi' => $item['oi'],
                    '_coi' => $item['coi'],
                    '_delta' => $item['delta'],
                    '_cvs' => $item['cvs'],
                    '_cvs_balance' => $item['cvs_balance'],
                    '_print' => $item['print'],
                );
            }

            if (count($data_for_insert) !== 0) {
                Log::info('Добавление основных данных.', [ 'table' => $this->table_month, 'data' => json_encode($data_for_insert), 'type' => $type, 'date' => $date ]);

                DB::table($this->table_month)->insert($data_for_insert);
            }
        } else {
            Log::warning('Нет основных данных.', [ 'table' => $this->table_month, 'type' => $type, 'date' => $date ]);
        }

        return $max_oi;
    }

    public function updateCmeDayTable($date, $data_call, $data_put, $pair)
    {
        $result = false;
        $strike = -1;
        $p_call = 0;
        $p_put = 0;

        if (count($data_call) !== 0) {
            $delta_diff = 0;

            foreach ($data_call as $item) {
                if (abs((float)$item['delta'] - 0.5) < $delta_diff || $delta_diff == 0) {
                    $delta_diff = abs((float)$item['delta'] - 0.5);
                    $strike = (int)$item['strike'];
                    $p_call = (float)$item['reciprocal'];
                }
            }
        } else {
            Log::warning('Нет данных call.', [ 'table' => $this->table_day, 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        if (count($data_put) !== 0) {
            foreach ($data_put as $item) {
                if ((int)$item['strike'] == $strike) {
                    $p_put = (float)$item['reciprocal'];
                }
            }
        } else {
            Log::warning('Нет данных put.', [ 'table' => $this->table_day, 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        if ($strike != -1) {
            if (!Schema::hasTable($this->table_day)) {
                $this->createDayTable();
            }

            if (($info = DB::table($this->table_day)->where([ ['_date', '=', $date], ['_symbol', '=', strtoupper($pair)] ])->first())) {
                Log::info('Изменение дневных данных.', [ 'table' => $this->table_day, 'strike' => $strike, 'p_call' => $p_call, 'p_put' => $p_put, 'id' => $info->_id, 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);

                DB::table($this->table_day)
                    ->where('_id', $info->_id)
                    ->update(
                        [
                            '_strike' => $strike,
                            '_p_call' => $p_call,
                            '_p_put' => $p_put
                        ]
                    );
            } else {
                Log::info('Добавление дневных данных.', [ 'table' => $this->table_day, 'strike' => $strike, 'p_call' => $p_call, 'p_put' => $p_put, 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);

                DB::table($this->table_day)
                    ->insert(
                        [
                            '_symbol' => strtoupper($pair),
                            '_date' => $date,
                            '_strike' => $strike,
                            '_p_call' => $p_call,
                            '_p_put' => $p_put
                        ]
                    );
            }

            $result = true;
        } else {
            Log::warning('Нет значения strike.', [ 'table' => $this->table_day, 'strike' => $strike, 'p_call' => $p_call, 'p_put' => $p_put, 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        return $result;
    }

    public function updatePairPrints($date, $max_oi)
    {
        $this_date_cme_data = DB::table($this->table_month)
            ->where('_date', '=', $date)
            ->get();

        if (count($this_date_cme_data) !== 0) {
            foreach ($this_date_cme_data as $item) {
                $print = 0;

                if ($item->_oi > $max_oi * 0.9) {
                    $print = 5;
                } elseif ($item->_oi > $max_oi * 0.8) {
                    $print = 4;
                } elseif ($item->_oi > $max_oi * 0.7) {
                    $print = 3;
                } elseif ($item->_oi > $max_oi * 0.6) {
                    $print = 2;
                } elseif ($item->_oi > $max_oi * 0.5) {
                    $print = 1;
                }

                if ($print > 0) {
                    Log::info('Изменение значений _print.', [ 'table' => $this->table_month, 'id' => $item->_id, 'date' => $date, 'max_oi' => $max_oi, 'print' => $print ]);

                    DB::table($this->table_month)
                        ->where('_id', $item->_id)
                        ->update(['_print' => $print]);
                }
            }
        } else {
            Log::warning('Ошибка даты при изменение значений _print.', [ 'table' => $this->table_month, 'date' => $date, 'max_oi' => $max_oi ]);
        }
    }

    public function finish($id, $update_e_time = true)
    {
        $date = strtotime(date('Y-m-d'));

        Log::info('Завершили парсинг таблицы.', [ 'table' => $this->table_month, 'id' => $id, 'time' => $date ]);

        if ($update_e_time) {
            DB::table('cme_options')
                ->where('_id', $id)
                ->update(['_e_time' => $this->pdf_files_date]);
        }

        DB::table($this->table_parser_settings)
            ->insert(
                [
                    '_symbol' => strtoupper($this->pair_with_major),
                    '_bulletin_date' => $this->pdf_files_date,
                    '_option' => $this->option->_id,
                    '_finish_date' => date('Y-m-d H:i:s')
                ]
            );
    }

    public function getDataFromJson()
    {
        $result = [];

        if (!empty($this->json_main_data_link) && !empty($this->pdf_files_date)) {
            $response = Curl::to($this->json_main_data_link)
                ->get();

            if (!empty($response)) {
                $main_data = json_decode($response, true);
                $main_data = $this->prepareMainDataFromJson($main_data);

                if (count($main_data) !== 0) {
                    $cnt = 1;
                    foreach ($main_data as $month => $month_items) {
                        // огранииваем число месяцев в парсинге
                        if ($cnt > $this->json_max_month_to_parse) {
                            break;
                        }

                        $year = preg_replace('/[a-zA-Z]/', '', $month);
                        $number = preg_replace('/[0-9]/', '', strtolower($month));
                        $json_other_data_link = str_replace(array('{option_product_id}', '{pair}', '{month}', '{year}', '{strategy}', '{bulletin_date}'), array($this->json_option_product_id, $this->json_pair_name, self::$json_month_associations[$number], $year, $this->json_strategy, date('m/d/Y', $this->pdf_files_date)), $this->json_other_data_link);

                        $response_other_data = Curl::to($json_other_data_link)
                            ->get();

                        if (!empty($response_other_data)) {
                            $other_data = json_decode($response_other_data, true);
                            $other_data = $this->prepareOtherDataFromJson($other_data);

                            if (count($other_data) !== 0) {
                                foreach ($month_items as $type => $strikes) {
                                    foreach ($strikes as $strike => $strike_data) {
                                        $result[$month][$type][$strike] = $strike_data;
                                        $result[$month][$type][$strike]['reciprocal'] = !empty($other_data[$type][$strike]['reciprocal']) ? (float)$other_data[$type][$strike]['reciprocal'] : 0;
                                    }
                                }

                                $cnt++;
                            } else {
                                unset($result[$month]);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getMonths($file, $current_option_month)
    {
        $months = array();
        $text = $this->extract($file);

        if ($text) {
            $start = strpos($text, $current_option_month);
            $end = strpos($text, $this->month_end);

            if ($start && $end) {
                $text = substr($text, $start, $end - $start);
                $text = str_replace(array($this->month_start, $this->month_end), '', $text);

                if ($text) {
                    $text_arr = explode(' ', $text);

                    if (count($text_arr) !== 0) {
                        $current_month_time = strtotime(preg_replace('/[a-zA-Z]/', '', $current_option_month) . '-' . self::$month_associations[preg_replace('/[0-9]/', '', strtolower($current_option_month))] . '-01');

                        foreach ($text_arr as $month) {
                            $month = trim($month);

                            $year = preg_replace('/[a-zA-Z]/', '', $month);
                            $number = preg_replace('/[0-9]/', '', strtolower($month));

                            $month_time = strtotime($year . '-' . self::$month_associations[$number] . '-01');
                            if ($month_time >= $current_month_time) {
                                $months[] = $month;
                            }
                        }
                    }
                }
            }
        }

        return $months;
    }

    public function isMonthAlreadyParsed()
    {
        $parser_info = DB::table($this->table_parser_settings)
            ->where(
                [
                    ['_symbol', '=', strtoupper($this->pair_with_major)],
                    ['_bulletin_date', '=', $this->pdf_files_date],
                    ['_option', '=', $this->option->_id]
                ]
            )->first();

        return empty($parser_info) ? false : true;
    }

    public function parse($update_e_time = true, $call = null, $put = null)
    {
        if (!$this->isMonthAlreadyParsed()) {
            if (!empty($this->option)) {
                $data_call = empty($call) ? $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL], $this->option->_option_month, self::CME_BULLETIN_TYPE_CALL) : $call;
                $data_put = empty($put) ? $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT], $this->option->_option_month, self::CME_BULLETIN_TYPE_PUT) : $put;
var_dump($data_call);die;
                if (count($data_call) && count($data_put)) {
                    $max_oi_call = 0;
                    $max_oi_put = 0;

                    if (count($data_call) !== 0) {
                        $max_oi_call = $this->addCmeData($this->pdf_files_date, $data_call, self::CME_BULLETIN_TYPE_CALL);
                    } else {
                        Log::warning('Не смогли получить основные данные дефолтным методом.', ['type' => self::CME_BULLETIN_TYPE_CALL, 'pair' => $this->pair, 'date' => $this->pdf_files_date]);
                    }
                    if (count($data_put) !== 0) {
                        $max_oi_put = $this->addCmeData($this->pdf_files_date, $data_put, self::CME_BULLETIN_TYPE_PUT);
                    } else {
                        Log::warning('Не смогли получить основные данные дефолтным методом.', ['type' => self::CME_BULLETIN_TYPE_PUT, 'pair' => $this->pair, 'date' => $this->pdf_files_date]);
                    }

                    if (DB::table($this->table_month)->where('_date', '=', $this->pdf_files_date)->first()) {
                        $this->addTotalCmeData($this->option->_id, $this->pdf_files_date, $data_call, $data_put);
                        $this->updatePairPrints($this->pdf_files_date, ($max_oi_call > $max_oi_put ? $max_oi_call : $max_oi_put));

                        if ($this->update_day_table === true && config('app.parser') == Base::PARSER_TYPE_PDF) {
                            $this->updateCmeDayTable($this->pdf_files_date, $data_call, $data_put, $this->pair_with_major);
                        }

                        $this->updateCvs($this->pdf_files_date, $data_call, $data_put);

                        if ($this->update_fractal_field_table == true) {
                            $this->updateIsFractal($this->pdf_files_date, $data_call, $data_put);
                        }
                    }

                    $this->finish($this->option->_id, $update_e_time);
                } else {
//                    Mail::raw('Нет данных PUT и CALL: pair - ' . $this->pair . ', date - ' . date('d.m.Y', $this->option_date) . ', pdf_files_date - ' . date('d.m.Y', $this->pdf_files_date), function($message) {
//                        $message->to(self::$email)->subject('Парсер сломался');
//                    });

                    Log::warning('Нет данных PUT и CALL.', ['pair' => $this->pair, 'date' => $this->option_date]);
                }
            } else {
                Log::warning('Нет файлов на дату.', ['pair' => $this->pair, 'date' => $this->option_date]);
            }
        }

        return true;
    }
    
    protected function extract($file)
    {
        $pdfToText = \XPDF\PdfToText::create();
        return is_file($file) ? $pdfToText->getText($file) : null;
    }

    protected function createParserSettingsTable()
    {
        Log::info('Была создана таблица.', [ 'table' => $this->table_parser_settings ]);

        Schema::create($this->table_parser_settings, function($table) {
            $table->increments('_id');
            $table->integer('_bulletin_date');
            $table->integer('_option');
            $table->char('_symbol', 15);
            $table->dateTime('_finish_date');
        });

        return true;
    }

    protected function createMonthTable()
    {
        Log::info('Была создана таблица.', [ 'table' => $this->table_month ]);

        Schema::create($this->table_month, function($table) {
            $table->increments('_id');
            $table->integer('_option');
            $table->integer('_date');
            $table->integer('_type');
            $table->integer('_strike');
            $table->double('_reciprocal', 7, 4);
            $table->integer('_volume');
            $table->integer('_oi');
            $table->integer('_coi');
            $table->double('_delta', 5, 4);
            $table->double('_cvs', 4, 2)->nullable();
            $table->tinyInteger('_cvs_balance')->nullable();
            $table->integer('_print')->nullable();
            $table->tinyInteger('_is_fractal')->default(0);
        });

        return true;
    }

    protected function createDayTable()
    {
        Log::info('Была создана таблица.', [ 'table' => $this->table_month ]);

        Schema::create($this->table_day, function($table) {
            $table->increments('_id');
            $table->char('_symbol', 15);
            $table->integer('_date');
            $table->integer('_strike');
            $table->double('_p_call', 7, 4);
            $table->double('_p_put', 7, 4);
        });

        return true;
    }

    protected function createTotalTable()
    {
        Log::info('Была создана таблица.', [ 'table' => $this->table_month ]);

        Schema::create($this->table_total, function($table) {
            $table->increments('_id');
            $table->integer('_option');
            $table->integer('_date');
            $table->integer('_total_oi_call');
            $table->integer('_total_volume_call');
            $table->integer('_change_oi_call');
            $table->integer('_total_oi_put');
            $table->integer('_total_volume_put');
            $table->integer('_change_oi_put');
        });

        return true;
    }

    protected function prepareArrayFromPdf($data)
    {
        $strike = (int)$data[14];
        $oi = (int)$data[6];
        $coi = (($data[7] == '+') ? 1 : -1 )*(int)$data[9];
        $delta = (float)$data[12];
        $reciprocal = (float)$data[4];
        $volume = (int)$data[5];

        return array(
            'strike' => $strike,
            'reciprocal' => $reciprocal,
            'volume' => $volume,
            'oi' => $oi,
            'coi' => $coi,
            'delta' => $delta,
            'cvs' => null,
            'cvs_balance' => null,
            'print' => null
        );
    }

    protected function clearEmptyStrikeValues($data)
    {
        foreach ($data as $key => $item) {
            if (empty($item['strike']) || (int)$item['strike'] <= 0) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function updateCvs($date, $data_call, $data_put)
    {
        $total_call = 0;
        $total_put = 0;
        $total = array();

        if (count($data_call) !== 0) {
            usort($data_call, function ($a, $b) {
                if ((int)$a['strike'] > (int)$b['strike']) return 1; else return -1;
            });

            foreach ($data_call as $key => $item) {
                $total_call += $item['oi'];

                for ($i = 0; $i <= $key; $i++) {
                    if (!isset($total[$item['strike']]['call'])) {
                        $total[$item['strike']]['call'] = 0;
                    }
                    $total[$item['strike']]['call'] += $data_call[$i]['oi'];
                }
            }
        } else {
            Log::warning('CVS: Нет данных call.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        if (count($data_put) !== 0) {
            usort($data_put, function ($a, $b) {
                if ((int)$a['strike'] > (int)$b['strike']) return -1; else return 1;
            });

            foreach ($data_put as $key => $item) {
                $total_put += $item['oi'];

                for ($i = 0; $i <= $key; $i++) {
                    if (!isset($total[$item['strike']]['put'])) {
                        $total[$item['strike']]['put'] = 0;
                    }
                    $total[$item['strike']]['put'] += $data_put[$i]['oi'];
                }
            }
        } else {
            Log::warning('CVS: Нет данных put.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        $balance_strike = 0;
        if (count($total) !== 0) {
            $balance = 0;

            foreach ($total as $strike => $data) {
                $call = 0;
                $put = 0;
                if (!empty($data['call']) && $total_call) {
                    $call = round(($data['call']/$total_call)*100, 2);

                    DB::table($this->table_month)
                        ->where(
                            [
                                ['_strike', '=', $strike],
                                ['_date', '=', $date],
                                ['_type', '=', self::CME_DB_BULLETIN_TYPE_CALL],
                            ]
                        )
                        ->update(['_cvs' => $call]);
                }
                if (!empty($data['put']) && $total_put) {
                    $put = round(($data['put']/$total_put)*100, 2);

                    DB::table($this->table_month)
                        ->where(
                            [
                                ['_strike', '=', $strike],
                                ['_date', '=', $date],
                                ['_type', '=', self::CME_DB_BULLETIN_TYPE_PUT],
                            ]
                        )
                        ->update(['_cvs' => $put]);
                }

                if (abs($call - $put) < $balance || $balance == 0) {
                    $balance = abs($call - $put);
                    $balance_strike = $strike;
                }
            }
        } else {
            Log::warning('CVS: Нет суммарных данных по страйкам.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        if ($balance_strike) {
            DB::table($this->table_month)
                ->where(
                    [
                        ['_strike', '=', $balance_strike],
                        ['_date', '=', $date],
                    ]
                )
                ->update(['_cvs_balance' => 1]);
        } else {
            Log::warning('CVS: Нет balance_strike.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        return true;
    }

    protected function updateIsFractal($date, $data_call, $data_put)
    {
        Log::info('Обновление фракталов.', [ 'table' => $this->table_month, '_date' => $date, '_call' => $data_call, '_put' => $data_put ]);

        if (count($data_call) !== 0) {
            for ($i = 1; $i < (count($data_call) - 2); $i++) {
                if ($data_call[$i]['volume'] > $data_call[$i - 1]['volume'] && 
                    $data_call[$i]['volume'] > $data_call[$i + 1]['volume'] && 
                    $data_call[$i - 1]['volume'] >= $this->min_fractal_volume &&
                    $data_call[$i + 1]['volume'] >= $this->min_fractal_volume) {

                    Log::info('Фрактал CALL.', [ 'table' => $this->table_month, '_date' => $date, '_strike' => $data_call[$i]['strike'],  '_call' => $data_call, '_put' => $data_put ]);

                    DB::table($this->table_month)
                        ->where(
                            [
                                ['_strike', '=', $data_call[$i]['strike']],
                                ['_date', '=', $date],
                                ['_type', '=', self::CME_DB_BULLETIN_TYPE_CALL],
                            ]
                        )
                        ->update(['_is_fractal' => 1]);
                }
            }
        } else {
            Log::warning('is_fractal: Нет данных call.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        if (count($data_put) !== 0) {
            for ($i = 1; $i < (count($data_put) - 2); $i++) {
                if ($data_put[$i]['volume'] > $data_put[$i - 1]['volume'] &&
                    $data_put[$i]['volume'] > $data_put[$i + 1]['volume'] &&
                    $data_put[$i - 1]['volume'] >= $this->min_fractal_volume &&
                    $data_put[$i + 1]['volume'] >= $this->min_fractal_volume) {

                    Log::info('Фрактал PUT.', [ 'table' => $this->table_month, '_date' => $date, '_strike' => $data_call[$i]['strike'],  '_call' => $data_call, '_put' => $data_put ]);

                    DB::table($this->table_month)
                        ->where(
                            [
                                ['_strike', '=', $data_put[$i]['strike']],
                                ['_date', '=', $date],
                                ['_type', '=', self::CME_DB_BULLETIN_TYPE_PUT],
                            ]
                        )
                        ->update(['_is_fractal' => 1]);
                }
            }
        } else {
            Log::warning('is_fractal: Нет данных put.', [ 'date' => $date, 'call' => json_encode($data_call), 'put' => json_encode($data_put) ]);
        }

        return true;
    }

    protected function getRows($file, $month, $type)
    {
        $out = array();

        if (is_file($file)) {
            $text = $this->extract($file);

            $start = strpos($text, $type == self::CME_BULLETIN_TYPE_CALL ? $this->start_index_call : $this->start_index_put);
            $text = substr($text, $start);

            $end = strpos($text, $type == self::CME_BULLETIN_TYPE_CALL ? $this->end_index_call : $this->end_index_put);
            $text = substr($text, 0, $end);

            $page_key = $type == self::CME_BULLETIN_TYPE_CALL ? $this->new_page_key_call : $this->new_page_key_put;

            if ($text) {
                $start = strpos($text, $month);

                if ($start) {
                    $text = substr($text, $start + strlen($month));
                    $end = strpos($text, 'TOTAL');
                    // получили нужный текст
                    $text = substr($text, 0, $end);

                    // теперь надо его избавить от страниц
                    // вариант, когда на новую страницу не перенеслись данные (например канадец от 2016-09-14)
                    if (strpos($text, $page_key) === false && strpos($text, 'THE INFORMATION CONTAINED IN THIS REPORT') !== false) {
                        $start_pos = strpos($text, 'THE INFORMATION CONTAINED IN THIS REPORT');
                        $text = substr($text, 0, $start_pos);
                    } else {
                        while ($pos = strpos($text, $page_key)) {
                            $start_pos = strpos($text, 'THE INFORMATION CONTAINED IN THIS REPORT');

                            if ($start_pos === false) {
                                $start_pos = $pos;
                            }

                            $text = substr($text, 0, $start_pos) . substr($text, $pos + strlen($page_key));
                        }
                    }

                    $pieces = explode("\n", $text);

                    $parser_element_key = 1;
                    $strike_key = 0;

                    $result = array();
                    foreach ($pieces as $item_key => $item) {
                        if (!in_array($item, array($month, '', '+', '-')) && strpos($item, 'FUTURES SETT.') === false) {
                            $result[$strike_key][] = $item;

                            if ($parser_element_key == 4) {
                                $strike_key++;
                                $parser_element_key = 1;
                            } else {
                                $parser_element_key++;
                            }
                        }
                    }

                    if (count($result) !== 0) {
                        foreach ($result as $key => $rows) {
                            $strike_data = array();
                            foreach ($rows as $row_key => $row) {
                                $row_arr = $this->prepareItemFromParse($row_key, $row);

                                if (count($row_arr) !== 0) {
                                    foreach ($row_arr as $row_arr_key => $item) {
                                        $strike_data[] = trim($item);
                                    }
                                }
                            }

                            $out[] = $this->prepareArrayFromPdf($strike_data);
                        }
                    }
                }
            }
        }

        return $this->clearEmptyStrikeValues($out);
    }

    protected function prepareItemFromParse($key, $data)
    {
        $result = array();

        switch ($key) {
            case 0:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 8) {
                    $result = $data_arr;
                } else if (count($data_arr) == 7) {
                    $result = array_merge($data_arr, array('+'));
                } else if (count($data_arr) == 9) {
                    foreach ($data_arr as $item) {
                        if (strpos($item, '@') === false) {
                            $result[] = $item;
                        }
                    }
                }

                break;

            case 1:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 4) {
                    $result = $data_arr;
                } else {

                }

                break;

            case 2:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 2) {
                    $result = $data_arr;
                }

                break;

            case 3:
                $data_arr = explode(' ', $data);

                if (count($data_arr) == 1) {
                    $result = $data_arr;
                }

                break;
        }

        return $result;
    }

    protected function prepareMainDataFromJson($items)
    {
        $result = [];

        if ($items['empty'] == false) {
            foreach ($items['monthData'] as $item) {
                $month_info_arr = explode('-', $item['monthID']);

                if (count($month_info_arr) == 3) {
                    $key = strpos(strtolower($item['label']), self::CME_BULLETIN_TYPE_CALL) !== false ? self::CME_BULLETIN_TYPE_CALL : self::CME_BULLETIN_TYPE_PUT;

                    if (count($item['strikeData']) !== 0) {
                        foreach ($item['strikeData'] as $strike_info) {
                            $result[$month_info_arr[0].$month_info_arr[1]][$key][(int)$strike_info['strike']] = [
                                'strike' => (int)$strike_info['strike'],
                                'reciprocal' => 0,
                                'volume' => (int)str_replace(array(','), array(''), $strike_info['totalVolume']),
                                'oi' => (int)str_replace(array(','), array(''), $strike_info['atClose']),
                                'coi' => (int)str_replace(array(','), array(''), $strike_info['change']),
                                'delta' => 0,
                                'cvs' => null,
                                'cvs_balance' => null,
                                'print' => null
                            ];
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected function prepareOtherDataFromJson($items)
    {
        $result = [];

        if ($items['empty'] == false) {
            foreach ($items['settlements'] as $item) {
                $key = strpos(strtolower($item['type']), self::CME_BULLETIN_TYPE_CALL) !== false ? self::CME_BULLETIN_TYPE_CALL : self::CME_BULLETIN_TYPE_PUT;
                $strike = ((int)$item['strike'])/$this->json_settle_strike_divide;

                $result[$key][$strike] = [
                    'strike' => $strike,
                    'reciprocal' => (float)$item['settle']*$this->json_settle_multiply
                ];
            }
        }

        return $result;
    }

    protected function createFieldIsFractal()
    {
        if (!Schema::hasTable($this->table_month)) {
            $this->createMonthTable();
        }

        if (!Schema::hasColumn($this->table_month, '_is_fractal')) {
            Schema::table($this->table_month, function ($table) {
                $table->tinyInteger('_is_fractal')->default(0);
            });
        }
    }
}