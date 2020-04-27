<?php

namespace SayaCloud\MafuComponents\RegularCustomer;

/**
 * Class RegularCustomer
 * @package SayaCloud\MafuComponents\RegularCustomer
 */
class RegularCustomer
{
    public static $error;

    private static $apiBaseUrl = '';
    private static $apiKey = '';
    private static $apiSecret = '';

    public static function config(array $config)
    {
        self::$apiKey = $config['app_key'];
        self::$apiSecret = $config['app_secret'];
        self::$apiBaseUrl = $config['api_base'];
    }

    public static function fetchRegularCustomerList($where, $page, $size)
    {
        $offset = ($page - 1) * $size;
        $where['offset'] = $offset;
        $where['size'] = $size;
        $resp = self::curlFetch('GET', '/regular-customers', $where);
        $result = json_decode($resp, true);
        if (!is_array($result) || !isset($result['code'], $result['data']) || $result['code'] != 200) {
            self::$error = isset($result['message']) ? $result['message'] : self::$error;
            return false;
        }
        return $result;
    }

    public static function fetchRegularCustomerByMobile($mobile)
    {
        $data['mobile'] = $mobile;
        $resp = self::curlFetch('GET', '/regular-customer', $data);
        $result = json_decode($resp, true);
        if (!is_array($result) || !isset($result['code'], $result['data']) || $result['code'] != 200) {
            self::$error = isset($result['message']) ? $result['message'] : self::$error;
            return false;
        }
        return $result['data'];
    }

    public static function addRegularCustomerInfo($name, $mobile, $origin = 'wxshop')
    {
        $data = [
            'name' => $name,
            'mobile' => $mobile,
            'origin' => $origin
        ];
        $resp = self::curlFetch('POST', '/regular-customer', $data);
        $result = json_decode($resp, true);
        self::$error = isset($result['message']) ? $result['message'] : self::$error;
        return !(!is_array($result) || !isset($result['code']) || $result['code'] != 200);
    }

    private static function curlFetch($requestMethod, $api, $data)
    {
        $url = self::$apiBaseUrl . $api;
        $header = [
            "Content-Type: application/x-www-form-urlencoded"
        ];
        if ($data) {
            $str = '';
            ksort($data);
            foreach ($data as $key => $val) {
                $str .= $key . $val;
            }
            $str .= self::$apiSecret;
            $signature = md5($str);
            $header[] = "Signature: $signature";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (strtoupper($requestMethod) === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else if ($data) {
            curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $resp = curl_exec($ch);

        $curl_info = [
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'err' => curl_errno($ch),
            'errmsg' => curl_error($ch),
            'response' => $resp
        ];
        if (!in_array($curl_info['http_code'], [200, 201, 204], true)) {
            self::$error = $curl_info['errmsg'];
        }

        curl_close($ch);
        return $resp;
    }

}

