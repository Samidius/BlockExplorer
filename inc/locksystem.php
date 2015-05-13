<?php

/**
 * Class locksystem
 * This will help you from having process run more then once.
 */
class locksystem
{
    public static function lock($file, $timeLimit)
    {
        $filename = __DIR__ . "\\" . $file;
        if (!file_exists($filename)) {
            $fp   = fopen($filename, "w");
            $contents['locked']     = "no";
            $contents['lastlocked'] = time();
            fwrite($fp, json_encode($contents));
            fclose($fp);
        }
        $handle   = fopen($filename, "r");
        $contents = json_decode(fread($handle, filesize($filename)), true);
        fclose($handle);
        $return['locked'] = "no";
        if ((int)$contents['lastlocked'] > strtotime("-" . $timeLimit . " minutes")) {
            if ($contents['locked'] == "no") {
                $contents['locked']     = "yes";
                $contents['lastlocked'] = time();
                $fp                     = fopen($filename, 'w');
                fwrite($fp, json_encode($contents));
                fclose($fp);
            } else {
                $return['locked'] = "yes";
            }
        } else {
            $contents['locked']     = "yes";
            $contents['lastlocked'] = time();
            $fp                     = fopen($filename, 'w');
            fwrite($fp, json_encode($contents));
            fclose($fp);
        }
        return $return;
    }

    public static function unlock($file)
    {
        $filename               = __DIR__ . "/" . $file;
        $contents['locked']     = "no";
        $contenst['lastlocked'] = time();
        $fp                     = fopen($filename, 'w');
        fwrite($fp, json_encode($contents));
        fclose($fp);
    }
}