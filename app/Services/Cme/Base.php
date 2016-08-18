<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:14
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Base
{
    const CME_BULLETIN_TYPE_CALL = 'call';
    const CME_BULLETIN_TYPE_PUT = 'put';

    const PAIR_EUR = 'EUR';
    const PAIR_GBP = 'GBP';
    const PAIR_JPY = 'JPY';
    const PAIR_CHF = 'CHF';
    const PAIR_AUD = 'AUD';
    const PAIR_CAD = 'CAD';
    const PAIR_USD = 'USD';

    protected $files;
    protected $pair_with_major;
    protected $option;
    protected $option_date;
    protected $table;
    protected $table_day;
    protected $table_total;
    protected $table_month;
    protected $cme_file_path;
    protected $pair;

    public function __construct($date = null) {
        $this->option_date = empty($date) ? strtotime(date("d-m-Y", (time() - 86400))) : $date;
        $this->files = $this->getFilesAssociations($this->pair);
        $this->table = 'cme_options';
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
        }
    }

    public function addCmeData($date, $data, $type)
    {
        $max_oi = 0;

        if (count($data) !== 0) {
            $data_for_insert = array();

            if (!Schema::hasTable($this->table_month)) {
                $this->createMonthTable();
            }

            $this_date_cme_data = DB::table($this->table_month)
                ->where(
                    [
                        ['_date', '=', $date],
                        ['_type', '=', ($type == self::CME_BULLETIN_TYPE_CALL ? 0 : 1)]
                    ]
                )
                ->first();

            foreach ($data as $key => $item) {
                if ((int)$item['oi'] > $max_oi){
                    $max_oi = (int)$item['oi'];
                }

                if (count($this_date_cme_data) === 0) {
                    $data_for_insert[] = array(
                        '_date' => $date,
                        '_type' => ($type == self::CME_BULLETIN_TYPE_CALL ? 0 : 1),
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
            }

            if (count($data_for_insert) !== 0) {
                DB::table($this->table_month)->insert($data_for_insert);
            }
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
        }

        if (count($data_put) !== 0) {
            foreach ($data_put as $item) {
                if ((int)$item['strike'] == $strike) {
                    $p_put = (float)$item['reciprocal'];
                }
            }
        }

        if ($strike != -1) {
            if (!Schema::hasTable($this->table_day)) {
                $this->createDayTable();
            }

            if (($info = DB::table($this->table_day)->where([ ['_date', '=', $date], ['_symbol', '=', strtoupper($pair)] ])->first())) {
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
                    DB::table($this->table_month)
                        ->where('_id', $item->_id)
                        ->update(['_print' => $print]);
                }
            }
        }
    }

    public function finish($id, $date)
    {
        DB::table('cme_options')
            ->where('_id', $id)
            ->update(['_e_time' => $date]);
    }
    
    protected function extract($file)
    {
        $result = array();
        $pdf_data = file_get_contents($file);

        if (strlen($pdf_data) < 1000 && file_exists($pdf_data)) {
            $pdf_data = file_get_contents($pdf_data);
        }
        if (!trim($pdf_data)) {
            echo "Error: there is no PDF data or file to process.";
        }

        if (preg_match_all('/<<[^>]*FlateDecode[^>]*>>\s*stream(.+)endstream/Uis', $pdf_data, $m)) {
            foreach ($m[1] as $chunk) {
                $chunk = gzuncompress(ltrim($chunk));
                $a = preg_match_all('/\[([^\]]+)\]/', $chunk, $m2) ? $m2[1] : array($chunk);

                foreach ($a as $sub_chunk) {
                    if (preg_match_all('/\(([^\)]+)\)/', $sub_chunk, $m3)) {
                        $result[] = $m3[1];
                    }
                }
            }
        } else {
            echo "Error: there is no FlateDecode text in this PDF file that I can process.";
        }

        return $result;
    }

    protected function createMonthTable()
    {
        Schema::create($this->table_month, function($table) {
            $table->increments('_id');
            $table->integer('_date');
            $table->integer('_type');
            $table->integer('_strike');
            $table->double('_reciprocal', 7, 4);
            $table->integer('_volume');
            $table->integer('_oi');
            $table->integer('_coi');
            $table->double('_delta', 5, 4);
            $table->double('_cvs', 4, 1)->nullable();
            $table->tinyInteger('_cvs_balance')->nullable();
            $table->integer('_print')->nullable();
        });
    }

    protected function createDayTable()
    {
        Schema::create($this->table_day, function($table) {
            $table->increments('_id');
            $table->char('_symbol', 15);
            $table->integer('_date');
            $table->integer('_strike');
            $table->double('_p_call', 7, 4);
            $table->double('_p_put', 7, 4);
        });
    }

    protected function createTotalTable()
    {
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
    }

    protected function prepareArrayFromPdf($data)
    {
        $strike = null;
        $reciprocal = null;
        $volume = null;
        $oi = null;
        $coi = null;
        $delta = null;
        $cvs = null;
        $cvs_balance = null;
        $print = null;

        if (count($data) == 14) {
            $strike = trim($data[13]);
            $reciprocal = str_replace(array('+', '-', 'CAB'), array('', '', '0'), $data[4]);

            if (strrpos($data[6], '-') > 0) {
                $data[8] = '-' . $data[8];
            }

            $oi = trim(str_replace(array('+', '-'), '', $data[6]));
            $coi = trim(str_replace('UNCH', '0', $data[8]));
            $volume = trim(str_replace("----", '0', $data[5]));
            $delta = trim(str_replace("----", '0', $data[11]));
        }

        return array(
            'strike' => $strike,
            'reciprocal' => $reciprocal,
            'volume' => $volume,
            'oi' => $oi,
            'coi' => $coi,
            'delta' => $delta,
            'cvs' => $cvs,
            'cvs_balance' => $cvs_balance,
            'print' => $print
        );
    }

    protected function clearEmptyStrikeValues($data)
    {
        foreach ($data as $key => $item) {
            if (empty($item['strike'])) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}