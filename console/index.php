#!/usr/bin/php
<?php
/**
 * Crypto Blockchain TO SQL System
 * Command line : php index.php (block=<blockid>)
 * Simple Configuration system
 *
 *
 */

require_once(__DIR__ . '/../inc/jsonrpc.php');
require_once(__DIR__ . '/../inc/rb.php');
require_once(__DIR__ . '/config.php');
function getDBBlockCount()
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

function getBlockCount()
{
    $wallet = new jsonRPCClient(HOTWALLET, true);
    $return = $wallet->getblockcount();
    return $return;
}

function processVin($vin)
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

function processVout($vout)
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

function processTX($tx)
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

function processTransactions($transactions)
{
    $totalValue       = 0;
    $transactionCount = 0;
    foreach ($transactions as $tx) {
        $transactionCount++;
        $wallet               = new jsonRPCClient(HOTWALLET, true);
        $getRawTransaction    = $wallet->getrawtransaction($tx);
        $decodeRawTransaction = $wallet->decoderawtransaction($getRawTransaction);
        $vin                  = processVin($decodeRawTransaction);
        $vout                 = processVout($decodeRawTransaction);
        $result               = processTX($decodeRawTransaction);
        $totalValue           = bcadd($vout['valueTotal'], $totalValue, 6);
    }
    $return['totalValue']       = $totalValue;
    $return['transactionCount'] = $transactionCount;
    return $return;
}

function copyBlockToDB($blockID)
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
                $processTX                  = processTransactions($value);
                $dispense->totalvalue       = $processTX['totalValue'];
                $dispense->transactionCount = $processTX['transactionCount'];
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

function processBlocks($block = null)
{
    $totalBlocks   = getBlockCount();
    $dbBlockCount  = getDBBlockCount();
    $totalDBBlocks = $dbBlockCount['r'];
    if ($block) {
        $block                  = (int)$block;
        $array['copyBlockToDB'] = copyBlockToDB($block);
    } else {
        if ($totalBlocks > $totalDBBlocks) {
            $block    = $totalDBBlocks + 1;
            $blockTen = $block + PROCESSBLOCKS;
            while ($block < $blockTen) {
                $block = (int)$block;
                echo date("F j, Y, g:i:s a") . " BLOCK #" . $block . "\n";
                $array['copyBlockToDB'] = copyBlockToDB($block);
                $block                  = $block + 1;
            }
        }
    }
    $totalDBBlocks          = getDBBlockCount();
    $array['totalBlocks']   = $totalBlocks;
    $array['totalDBBlocks'] = $totalDBBlocks;
    $return                 = json_encode($array);
    return $return;
}

$filename = __DIR__. "/blockcheck.txt";
$handle   = fopen($filename, "r");
$contents = json_decode(fread($handle, filesize($filename)), true);
fclose($handle);
if ($contents['locked'] == "no") {
    $contents['locked'] = "yes";
    $fp                 = fopen($filename, 'w');
    fwrite($fp, json_encode($contents));
    fclose($fp);
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if (isset($_GET['block'])) {
        echo processBlocks($_GET['block']);
    } else {
        echo processBlocks();
    }
    $contents['locked'] = "no";
    $fp                 = fopen($filename, 'w');
    fwrite($fp, json_encode($contents));
    fclose($fp);
} else {
    echo "Process already running.";
    die();
}