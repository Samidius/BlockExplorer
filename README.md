# BlockExplorer
BlockExplorer System for Paycoin or any other CryptoCurrency

## Setup

### Configuration

In root folder, open `config.php` and set your DB and Coind connection details.


    define("DBHOSTNAME", "db-server-ip"); #use "localhost" if local
    define("DBDATABASE", "your-db-name");
    define("DBUSERNAME", "your-db-user");
    define("DBPASSWORD", "your-db-password");
    
    define("WALLETUSER", "your-coind-user");
    define("WALLETPASS", "your-coind-password");
    define("WALLETIP", "coind-server-ip"); #use "localhost" if local
    define("WALLETPORT", "coind-server-rpcport");


### Linux Crontab

Note: If you are using CentOS 6 minimal, crontab is not included. Run `yum install vixie-cron` to install it.

Edit your crontab by running `crontab -e`.

On a new line, enter:
`*/5 * * * * /usr/bin/php /path/to/console/run.php`

This will run the cronjob every 5 minutes.

### Windows Scheduler

