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

use DB;

class GetFilesFromFTP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getFilesFromFTP';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get *.pdf files from CME FTP website';

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
        $path_prefix = $disk->getDriver()->getAdapter()->getPathPrefix();

        // установка соединения
        $conn_id = ftp_connect(env('CME_FTP_URL'));
        // вход с именем пользователя и паролем
        $login_result = ftp_login($conn_id, env('CME_FTP_LOGIN'), '');

        if ($login_result == true) {
            ftp_pasv($conn_id, true);

            // получить содержимое текущей директории
            $contents = ftp_nlist($conn_id, env('CME_FTP_CONTENTS_FOLDER') . '/');

            $max_date = 0;
            $last_file = "";

            if (!empty($contents)) {
                foreach ($contents as $content) {
                    $date_timestamp = strtotime(substr($content, 28, 8));
                    if ((int)$date_timestamp > (int)$max_date) {
                        $max_date = $date_timestamp;
                        $last_file = $content;
                    }
                }

                $last_file = str_replace(env('CME_FTP_CONTENTS_FOLDER') . '/', '', $last_file);

                $disk->makeDirectory(env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/');

                // попытка скачать и распаковать архив
                $local_file = env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/' . substr($last_file, 0, 26) . substr($last_file, -4);
                $ftp_file = 'ftp://' . env('CME_FTP_URL') . '/' . env('CME_FTP_CONTENTS_FOLDER') . '/' . $last_file;

                if (count($disk->files(env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/' . substr($last_file, 0, 26) . '/')) == 0) {
                    if (!$disk->exists($local_file)) {
                        if (copy($ftp_file, $path_prefix . $local_file)) {
                            $zip = new \ZipArchive;

                            if ($zip->open($path_prefix . $local_file) === true) {
                                $disk->makeDirectory(env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/' . substr($last_file, 0, 26) . '/');

                                $zip->extractTo($path_prefix . env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/' . substr($last_file, 0, 26) . '/');
                                $zip->close();

                                Log::info('Файлы скопированы и распакованы.', [ 'file' => $local_file ]);
                            } else {
                                Log::warning('Файлы не были скопированы и распакованы.', [ 'file' => $local_file ]);
                            }
                        }
                    } else {
                        Log::warning('Файл уже скопирован и распакован.', [ 'file' => $local_file ]);
                    }

                    $disk->delete($local_file);
                    ftp_close($conn_id);
                } else {
                    Log::warning('Директория уже не пуста.', [ 'folder' => $disk->files(env('CME_PARSER_SAVE_FOLDER') . '/' . substr($last_file, 18, 4) . '/' . substr($last_file, 0, 26) . '/') ]);
                }
            } else {
                Log::warning('Не смогли получить содержимое папки bulletin по ftp.');
            }
        }
    }
}
