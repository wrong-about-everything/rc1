<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Method\FromString as HttpMethodFromString;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\FromString as UrlFromString;

class DefaultInbound implements Request
{
    public function method(): Method
    {
        return new HttpMethodFromString($_SERVER['REQUEST_METHOD']);
    }

    public function url(): Url
    {
        return
            new UrlFromString(
                sprintf(
                    '%s://%s%s',
                    isset($_SERVER['HTTPS']) ? 'https' : 'http',
                    $_SERVER['HTTP_HOST'],
                    $_SERVER['REQUEST_URI']
                )
            );
    }

    /**
     * Taken shamelessly from here: https://github.com/ralouphie/getallheaders
     */
    public function headers(): array/*Map<String, String>*/
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

    public function body(): string
    {
        return file_get_contents('php://input');
    }
}