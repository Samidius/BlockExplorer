<?php
/**
 * JSON RPC SERVER to process all calls for the stored Blockchain
 * Version      : 0.1.0
 * Last Updated : 5/13/2015
 */

require_once(__DIR__ . '/inc/rb.php');
require_once(__DIR__ . '/inc/blockchain.php');
require_once(__DIR__ . '/config.php');
define("VERSION","0.1.0");

class jsonRPCServer {
    public static function handle($object) {
        // checks if a JSON-RCP request has been received
        if (
            $_SERVER['REQUEST_METHOD'] != 'POST' ||
            empty($_SERVER['CONTENT_TYPE']) ||
            $_SERVER['CONTENT_TYPE'] != 'application/json'
        ) {
            // This is not a JSON-RPC request
            return false;
        }
        // reads the input data
        $request = json_decode(file_get_contents('php://input'),true);
        // executes the task on local object
        try {
            //$return = $getapi->$api[3]($api,$user,$_POST,$_SERVER,$_REQUEST);
            if ($result = @call_user_func_array(array($object,$request['method']),$request['params'])) {
                $response = array (
                    'id' => $request['id'],
                    'result' => $result,
                    'error' => NULL
                );
            } else {
                $response = array (
                    'id' => $request['id'],
                    'result' => NULL,
                    'error' => 'unknown method or incorrect parameters '
                );
            }
        } catch (Exception $e) {
            $response = array (
                'id' => $request['id'],
                'result' => NULL,
                'error' => $e->getMessage()
            );
        }
        // output the response
        if (!empty($request['id'])) { // notifications don't want response
            header('content-type: text/javascript');
            echo json_encode($response);
        }
        // finish
        return true;
    }
}
$redirectURL = ltrim($_SERVER['REQUEST_URI'],'/');
$api = explode("/", $redirectURL);
$getAPI = call_user_func(array($api[2], "link"));
jsonRPCServer::handle($getAPI)
or print 'no request';