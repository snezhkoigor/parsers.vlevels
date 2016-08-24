<?php

namespace App\Console\Commands;

use App\Services\Cme\ForwardPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use DB;

class GetForwardPointsFromFTP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetForwardPointsFromFTP';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get forward points files from CME FTP website';

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
        $disk = Storage::disk('public');
        $path_prefix = $disk->getDriver()->getAdapter()->getPathPrefix();

        // установка соединения
        $conn_id = ftp_connect(env('CME_FTP_URL'));
        // вход с именем пользователя и паролем
        $login_result = ftp_login($conn_id, env('CME_FTP_LOGIN'), '');

        if ($login_result == true) {
            ftp_pasv($conn_id, true);

            // получить содержимое текущей директории
            $contents = ftp_nlist($conn_id, env('CME_FTP_CONTENTS_FORWARD_POINTS_FOLDER') . '/');

            if (!empty($contents)) {
                $last_file = array_pop($contents);
                $last_file = str_replace(env('CME_FTP_CONTENTS_FORWARD_POINTS_FOLDER') . '/', '', $last_file);

                $name_arr = explode('-', $last_file);

                $disk->makeDirectory(env('CME_PARSER_FORWARD_POINTS_SAVE_FOLDER') . '/' . $name_arr[1] . '/');

                // попытка скачать и распаковать архив
                $local_file = env('CME_PARSER_FORWARD_POINTS_SAVE_FOLDER') . '/' . $name_arr[1] . '/' . $last_file;
                $ftp_file = 'ftp://' . env('CME_FTP_URL') . '/' . env('CME_FTP_CONTENTS_FORWARD_POINTS_FOLDER') . '/' . $last_file;

                if (!$disk->has($local_file)) {
                    if (copy($ftp_file, $path_prefix . $local_file)) {
                        
                    } else {
                        Log::warning('Файл не смогли скопировать.', [ 'file' => $local_file ]);
                    }
                } else {
                    Log::warning('Файл уже скачан.', [ 'file' => $local_file ]);
                }
                
                if ($local_file) {
                    $forward_point = new ForwardPoint();

                    $forward_point->parse($local_file);
                }
            }
        }
    }
}
