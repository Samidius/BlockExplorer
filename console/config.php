<?php
/**
 * Start ProcessBlocks
 * Set The amount of Blocks it will process
 */
define("PROCESSBLOCKS", 1000);
/**
 * End ProcessBlocks
 */

/**
 * Start MySQL Configuration
 */

define("DBHOSTNAME", "localhost");
define("DBDATABASE", "ledger");
define("DBUSERNAME", "root");
define("DBPASSWORD", "");
R::setup('mysql:host=' . DBHOSTNAME . ';dbname=' . DBDATABASE, DBUSERNAME, DBPASSWORD);

/**
 * End MySQL Configuration
 */

/**
 * Start RPC Link to Wallet
 * wallet config file needs
 * txindex=1
 * rpcuser=primes(username)
 * rpcpassword=localbatman(password)
 * rpcallowip=192.168.1.247(ipofserverhostingthisscript)
 * rpcport=9005(port DEFAULT:8889)
 * bind=0.0.0.0:8888
 * debug=1
 */

define("WALLETUSER", "primes");
define("WALLETPASS", "localbatman");
define("WALLETIP", "192.168.1.247");
define("WALLETPORT", "9005");
define("HOTWALLET", "http://" . WALLETUSER . ":" . WALLETPASS . "@" . WALLETIP . ":" . WALLETPORT . "");

/**
 * End RPC Link to Wallet
 */