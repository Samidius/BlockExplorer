<?php

/**
 * Class locksystem
 * This will help you from having process run more then once.
 */
class locksystem
{
    /**
     * @param $file - Filename of your lock file
     * @param $timeLimit - How many mintues before it the process runs anyways.
     * @return Array - ['locked'] (yes/no)
     */
    public static function lock($file, $timeLimit)
    {
        if (self::systemcheck() == "win") {
            $fileName = __DIR__ . "\\" . $file;
        } else {
            $fileName = __DIR__ . "/" . $file;
        }
        if (!file_exists($fileName)) {
            $fp   = fopen($fileName, "w");
            $contents['locked']     = "no";
            $contents['lastlocked'] = time();
            fwrite($fp, json_encode($contents));
            fclose($fp);
        }
        $handle   = fopen($fileName, "r");
        $contents = json_decode(fread($handle, filesize($fileName)), true);
        fclose($handle);
        $return['locked'] = "no";
        if ((int)$contents['lastlocked'] > strtotime("-" . $timeLimit . " minutes")) {
            if ($contents['locked'] == "no") {
                $contents['locked']     = "yes";
                $contents['lastlocked'] = time();
                $fp                     = fopen($fileName, 'w');
                fwrite($fp, json_encode($contents));
                fclose($fp);
            } else {
                $return['locked'] = "yes";
            }
        } else {
            $contents['locked']     = "yes";
            $contents['lastlocked'] = time();
            $fp                     = fopen($fileName, 'w');
            fwrite($fp, json_encode($contents));
            fclose($fp);
        }
        return $return;
    }

    /**
     * @param $file - Filename of your lock file
     */
    public static function unlock($file)
    {
        if (self::systemcheck() == "win") {
            $fileName = __DIR__ . "\\" . $file;
        } else {
            $fileName = __DIR__ . "/" . $file;
        }
        $contents['locked']     = "no";
        $contenst['lastlocked'] = time();
        $fp                     = fopen($fileName, 'w');
        fwrite($fp, json_encode($contents));
        fclose($fp);
    }

    /**
     * @return string - (win/other)
     */
    private static function systemcheck() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return "win";
        } else {
            return "other";
        }
    }
}