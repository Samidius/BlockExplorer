#!/usr/bin/php
<?php
error_reporting(0);
/**
 * Crypto Blockchain TO SQL System
 * Command line :
 *    Windows: php.exe run.php
 *    Linux:   php run.php
 * Version      : 0.1.0
 * Last Updated : 5/13/2015
 */

require_once(__DIR__ . '/../inc/jsonrpc.php');
require_once(__DIR__ . '/../inc/rb.php');
require_once(__DIR__ . '/../inc/locksystem.php');
require_once(__DIR__ . '/../config.php');
define("VERSION","0.1.0");
/**
 * Class console
 */
class console
{
    /**
     * @return Array - ['r'] (ID of last block), ['e'] (Error msg)
     */
    private static function getDBBlockCount()
    {
        try {
            $findOne = R::findOne('blocks', 'order by height desc');
            if ($findOne) {
                $return['r'] = $findOne->id;
            } else {
                $return['r'] = null;
            }
        } catch (Exception $e) {
            $return['e'] = $e;
            $return['r'] = null;
        }
        return $return;
    }

    /**
     * @return Array - JSON RPC ARRAY
     */
    private static function getBlockCount()
    {
        $wallet = new jsonRPCClient(HOTWALLET, true);
        $return = $wallet->getblockcount();
        return $return;
    }

    /**
     * @param $vin - JSON RPC DATA
     * @return string
     * @throws \RedBeanPHP\RedException
     */
    private static function processVin($vin)
    {
        $processVin = $vin['vin'];
        foreach ($processVin as $nextVin) {
            $findOne        = R::dispense('vin');
            $findOne->txidp = $vin['txid'];
            foreach ($nextVin as $key => $value) {
                if ($key == "scriptSig") {
                    foreach ($value as $ke => $val) {
                        $findOne->$ke = $val;
                    }
                } else {
                    $findOne->$key = $value;
                }
            }
            R::store($findOne);
        }
        return "Completed";
    }

    /**
     * @param $vout - JSON RPC DATA
     * @return mixed
     * @throws \RedBeanPHP\RedException
     */
    private static function processVout($vout)
    {
        $valueTotal  = 0;
        $processVout = $vout['vout'];
        foreach ($processVout as $nextVout) {
            $findOne        = R::dispense('vout');
            $findOne->txidp = $vout['txid'];
            foreach ($nextVout as $key => $value) {
                if ($key == "value") {
                    $valueTotal = bcadd($value, $valueTotal, 6);
                }
                if ($key == "scriptPubKey") {
                    foreach ($value as $ke => $val) {
                        if ($ke == "addresses") {
                            $findOne->addresses = json_encode($val);
                        } else {
                            $findOne->$ke = $val;
                        }
                    }
                } else {
                    $findOne->$key = $value;
                }
            }
            R::store($findOne);
        }
        $return['valueTotal'] = $valueTotal;
        return $return;
    }

    /**
     * @param $tx - JSON RPC DATA
     * @return string
     * @throws \RedBeanPHP\RedException
     */
    private static function processTX($tx)
    {
        $dispense = R::dispense('transactions');
        foreach ($tx as $key => $value) {
            if ($key == "vin") {
            } elseif ($key == "vout") {
            } else {
                $dispense->$key = $value;
            }
        }
        R::store($dispense);
        return "Completed";
    }

    /**
     * @param $transactions - ARRAY DATA
     * @return mixed
     */
    private static function processTransactions($transactions)
    {
        $totalValue       = 0;
        $transactionCount = 0;
        foreach ($transactions as $tx) {
            $transactionCount++;
            $wallet               = new jsonRPCClient(HOTWALLET, true);
            $getRawTransaction    = $wallet->getrawtransaction($tx);
            $decodeRawTransaction = $wallet->decoderawtransaction($getRawTransaction);
            $vin                  = self::processVin($decodeRawTransaction);
            $vout                 = self::processVout($decodeRawTransaction);
            $result               = self::processTX($decodeRawTransaction);
            $totalValue           = bcadd($vout['valueTotal'], $totalValue, 6);
        }
        $return['totalValue']       = $totalValue;
        $return['transactionCount'] = $transactionCount;
        return $return;
    }

    /**
     * @param $blockID - Block ID to process
     * @return mixed
     * @throws \RedBeanPHP\RedException
     */
    private static function copyBlockToDB($blockID)
    {
        $wallet       = new jsonRPCClient(HOTWALLET, true);
        $getBlockHash = $wallet->getblockhash($blockID);
        $getBlock     = $wallet->getblock($getBlockHash);
        //$return['getBlockHash'] = $getBlockHash;
        //$return['getBlock']     = $getBlock;
        $findOne = R::findOne('blocks', 'height = ?', [$blockID]);
        if (!$findOne) {
            $dispense = R::dispense('blocks');
            foreach ($getBlock as $key => $value) {
                if ($key == "tx") {
                    $dispense->$key             = json_encode($value);
                    $processTX                  = self::processTransactions($value);
                    $dispense->totalvalue       = $processTX['totalValue'];
                    $dispense->transactioncount = $processTX['transactionCount'];
                } else {
                    $dispense->$key = $value;
                }
            }
            R::store($dispense);
        }
        //$return['processTX'] = $processTX;
        $return['data'] = "Processed";
        return $return;
    }

    /**
     * @param null $block
     * @return string
     */
    public static function processBlocks($block = null)
    {
        $totalBlocks   = self::getBlockCount();
        $dbBlockCount  = self::getDBBlockCount();
        $totalDBBlocks = $dbBlockCount['r'];
        if ($block) {
            $block                  = (int)$block;
            $array['copyBlockToDB'] = self::copyBlockToDB($block);
        } else {
            if ($totalBlocks > $totalDBBlocks) {
                $block    = $totalDBBlocks + 1;
                $blockTen = $block + PROCESSBLOCKS;
                while ($block < $blockTen) {
                    $block = (int)$block;
                    echo "Ver: ".VERSION." Date/Time: ".date("F j, Y, g:i:s a") . " BLOCK #: " . $block . "\n";
                    $array['copyBlockToDB'] = self::copyBlockToDB($block);
                    $block                  = $block + 1;
                }
            }
        }
        $totalDBBlocks          = self::getDBBlockCount();
        $array['totalBlocks']   = $totalBlocks;
        $array['totalDBBlocks'] = $totalDBBlocks;
        $return                 = json_encode($array);
        return $return;
    }
}

$locked = locksystem::lock("blockcheck.txt", 15);
if ($locked['locked'] == "no") {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if (isset($_GET['block'])) {
        echo console::processBlocks($_GET['block']);
    } else {
        echo console::processBlocks();
    }
    $locked = locksystem::unlock("blockcheck.txt");
} else {
    echo "Process already running.";
    die();
}