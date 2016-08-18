<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:18
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Jpy extends Base
{
    public function __construct($date = null)
    {
        $this->pair = self::PAIR_JPY;

        parent::__construct($date);
        
        $this->pair_with_major = self::PAIR_USD.self::PAIR_JPY;
        $this->option = DB::table($this->table)
            ->where(
                [
                    ['_expiration', '>', $this->option_date],
                    ['_symbol', '=', $this->pair_with_major]
                ]
            )
            ->orderBy('_expiration')
            ->first();
        
        $this->table_day = 'cme_day_'.strtolower($this->pair_with_major);
        $this->table_total = 'cme_bill_'.strtolower($this->pair_with_major).'_total';
        $this->table_month = 'cme_bill_'.strtolower($this->pair_with_major).'_'.strtolower($this->option->_option_month);
        $this->cme_file_path = Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix() . env('CME_PARSER_SAVE_FOLDER') . '/' . date('Y', $this->option_date) . '/' . env('CME_BULLETIN_FOLDER_PREFIX') . date('Ymd', $this->option_date) . '/'; 
    }

    public function parse()
    {
        if (!empty($this->option)) {
            $data_call = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL], $this->option->_option_month, self::CME_BULLETIN_TYPE_CALL);
            $data_put = $this->getRows($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT], $this->option->_option_month, self::CME_BULLETIN_TYPE_PUT);

            $max_oi_call = 0;
            $max_oi_put = 0;
            if (count($data_call) !== 0) {
                $max_oi_call = $this->addCmeData($this->option_date, $data_call, self::CME_BULLETIN_TYPE_CALL);
            }
            if (count($data_put) !== 0) {
                $max_oi_put = $this->addCmeData($this->option_date, $data_put, self::CME_BULLETIN_TYPE_PUT);
            }

            if (DB::table($this->table_month)->where('_date', '=', $this->option_date)->first()) {
                $this->addTotalCmeData($this->option->_id, $this->option_date, $data_call, $data_put);
                $this->updatePairPrints($this->option_date, ($max_oi_call > $max_oi_put ? $max_oi_call : $max_oi_put));
                $this->updateCmeDayTable($this->option_date, $data_call, $data_put, $this->pair_with_major);
            }

            $this->finish($this->option->_id, $this->option_date);
        }

        return true;
    }

    private function getRows($file, $month, $type)
    {
        $text = array();
        $out = array();

        $content = $this->extract($file);

        if ($content) {
            foreach ($content as $page) {
                $key_final = array_search('FINAL', $page);

                if ($key_final === false) {
                    $key_final = array_search('PRELIMINARY', $page);
                }

                $buf = array_slice($page, 0, $key_final + 4);
                $key_total = array_search('TOTAL', $buf);

                if ($key_total === false) {
                    $page = array_slice($page, $key_final + 4);   //Убираем заголовок старницы
                } else {
                    $page = array_slice($page, $key_total);
                }

                $i = 0;
                foreach ($page as $p) {
                    if (strpos($p, 'THE INFORMATION CONTAINED IN THIS REPORT IS COMPILED') !== false) {
                        break;
                    }

                    $i++;
                }

                $page = array_slice($page, 0, $i);
                $text = array_merge($text, $page);
            }

            if ($type == self::CME_BULLETIN_TYPE_CALL) {
                $key_prefix = array_search('JAPAN YEN CALL', $text);
            } else {
                $key_prefix = array_search('JAPAN YEN PUT', $text);
            }

            if ($key_prefix !== false) {
                $text = array_slice($text, $key_prefix + 1);
                $key_postfix=-1;

                for ($i=0; $i<count($text); $i++) {
                    if (preg_match('/^WKJY/',$text[$i])) {
                        $key_postfix=$i;
                        break;
                    }
                }

                if ($key_postfix != -1) {
                    $text = array_slice($text, 0, $key_postfix);
                    $key_prefix = array_search($month, $text);

                    if ($text[$key_prefix+1] == $month) {
                        $key_prefix++;
                    }

                    if ($key_prefix !== false) {
                        $text = array_slice($text, $key_prefix, count($text));
                        $key_postfix = array_search('TOTAL', $text);
                        $text_month = array_slice($text, 1, $key_postfix - 1);
                        $text_month = array_chunk($text_month, 14);

                        foreach ($text_month as $t) {
                            $out[] = $this->prepareArrayFromPdf($t);
                        }
                    }
                }
            }
        }

        return $this->clearEmptyStrikeValues($out);
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
            $strike = trim($data[10]);
            $reciprocal = str_replace(array('+', '-', 'CAB'), array('', '', '0'), $data[4]);

            if (strrpos($data[5], '-') > 0) {
                $data[7] = '-' . $data[7];
            }

            $oi = trim(str_replace(array('+', '-'), '', $data[5]));
            $coi = trim(str_replace('UNCH', '0', $data[7]));
            $volume = trim(str_replace("----", '0', $data[11]));
            $delta = trim(str_replace("----", '0', $data[12]));
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
}