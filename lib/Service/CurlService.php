<?php

/**
 * User: Srdi
 * Date: 18-Feb-17
 * Time: 20:42
 */


declare(strict_types=1);

namespace OCA\EcloudAccounts\Service;
use Exception;

class CurlService
{
    /**
     * GET alias for request method
     *
     * @param $url
     * @param array $params
     * @param array $headers
     * @param array $userOptions
     * @return mixed
     */
    public function get($url, $params = array(), $headers = array(), $userOptions = array())
    {
        return $this->request('GET', $url, $params, $headers, $userOptions);
    }

    /**
     * POST alis for request method
     *
     * @param $url
     * @param array $params
     * @param array $headers
     * @param array $userOptions
     * @return mixed
     */
    public function post($url, $params = array(), $headers = array(), $userOptions = array())
    {
        return $this->request('POST', $url, $params, $headers, $userOptions);
    }

    public function delete($url, $params = [], $headers = [], $userOptions = []) {
        return $this->request('DELETE', $url, $params, $headers, $userOptions);
    }


    /**
     * Curl run request
     *
     * @param $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param array $userOptions
     * @return mixed
     * @throws Exception
     */
    private function request($method, $url, $params = array(), $headers = array(), $userOptions = array())
    {
        $ch = curl_init();
        $method = strtoupper($method);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        );
        array_merge($options, $userOptions);
        switch ($method) {
            case 'GET':
                if ($params) {
                    $url = $url . '?' . http_build_query($params);
                }
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $params;
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = "DELETE";
                if ($params) {
                    $url = $url . '?' . http_build_query($params);
                }
                break;
            default:
                throw new Exception('Unsuported method.');
                break;
        }
        $options[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if ($errno = curl_errno($ch)) {
            var_dump($errno);
            $errorMessage = curl_strerror($errno);
            throw new Exception("Curl error $errno - $errorMessage");
        }

        curl_close($ch);

        return $response;
    }
}
