<?php

class Logger
{
    private static function write($level, $message, $data = null)
    {
        $dir = __DIR__ . '/../logs';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir . '/app.log';

        $time = date('Y-m-d H:i:s');

        $log = "[$time] [$level] $message";

        if ($data !== null) {
            $log .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $log .= PHP_EOL;

        file_put_contents($file, $log, FILE_APPEND);
    }

    public static function info($message, $data = null)
    {
        self::write("INFO", $message, $data);
    }

    public static function error($message, $data = null)
    {
        self::write("ERROR", $message, $data);
    }

    public static function debug($message, $data = null)
    {
        self::write("DEBUG", $message, $data);
    }
}