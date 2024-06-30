<?php
namespace com\icemalta\kahuna\api\helper;

class ApiHelper 
{
    private const ALLOWED_ORIGINS = ['http://localhost:8001'];
    private const ALLOWED_METHODS = 'GET, POST, OPTIONS, PUT, PATCH, DELETE';

    public static function handleCors(): void
    {
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            if (in_array($_SERVER['HTTP_ORIGIN'], self::ALLOWED_ORIGINS)) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');    // cache for 1 day
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                // may also be using PUT, PATCH, HEAD etc
                header('Access-Control-Allow-Methods: ' . self::ALLOWED_METHODS);

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }
    }

    public static function getRequestData(): array|null
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        switch ($requestMethod) {
            case 'GET':
                return $_GET;
            case 'POST':
                return $_POST;
            case 'PATCH':
                parse_str(file_get_contents('php://input'), $requestData);
                ApiHelper::parse_raw_http_request($requestData);
                return is_array($requestData) ? $requestData : [];
            case 'DELETE':
                return [];
            default:
                return null;
        }
    }
    public static function parse_raw_http_request(array &$a_data)
    {
        $input = file_get_contents('php://input');

        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        foreach ($a_blocks as $id => $block) {
            if (empty($block))
                continue;

            if (strpos($block, 'application/octet-stream') !== FALSE) {
                preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $block, $matches);
            }
            else {
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $a_data[$matches[1]] = $matches[2];
        }
    }
}