<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 17.08.16
 * Time: 17:18
 */

namespace App\Services\Cme;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Aud extends Base
{
    public function __construct($date = null)
    {
        $this->pair = self::PAIR_AUD;

        parent::__construct($date);
        
        $this->pair_with_major = self::PAIR_AUD.self::PAIR_USD;
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
        if (!empty($this->option) && is_file($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_CALL]) && is_file($this->cme_file_path . $this->files[self::CME_BULLETIN_TYPE_PUT])) {
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
                $this->updateCvs($this->option_date, $data_call, $data_put);
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
                $base_key_prefix = array_search('AUST DLR CALL', $text);
            } else {
                $base_key_prefix = array_search('AUST DLR PUT', $text);
            }

            if ($base_key_prefix !== false) {
                $text = array_slice($text, $base_key_prefix + 1);

                if ($type == self::CME_BULLETIN_TYPE_CALL) {
                    $key_postfix = array_search('AUST DLR C EU', $text);
                } else {
                    $key_postfix = array_search('AUST DLR P EU', $text);
                }

                if ($key_postfix !== false) {
                    $text = array_slice($text, 0, $key_postfix);
                    $key_prefix = array_search($month, $text);

                    if ($text[$key_prefix+1] == $month) {
                        $key_prefix++;
                    }

                    if ($key_prefix !== false) {
                        $text = array_slice($text, $key_prefix, count($text));
                        $key_postfix = array_search('TOTAL', $text);
                        $text_month = array_slice($text, 1, $key_postfix - 1);
                        $key_prefix = array_search($month, $text_month);

                        if ($key_prefix !== false) {
                            unset($text_month[$key_prefix]);
                        }

                        $text_month = array_chunk($text_month, 14);
                        foreach ($text_month as $t) {
                            $out[] = $this->prepareArrayFromPdf($t);
                        }
                    } else {
                        Log::warning('Не смогли получить key_prefix файла .pdf (' . $file . '), month: ' . $month . ', type: ' . $type);
                    }
                } else {
                    Log::warning('Не смогли получить key_postfix файла .pdf (' . $file . '), month: ' . $month . ', type: ' . $type);
                }
            } else {
                Log::warning('Не смогли получить base_key_prefix файла .pdf (' . $file . '), month: ' . $month . ', type: ' . $type);
            }
        } else {
            Log::warning('Не смогли получить содержимое файла .pdf (' . $file . '), month: ' . $month . ', type: ' . $type);
        }

        return $this->clearEmptyStrikeValues($out);
    }
}