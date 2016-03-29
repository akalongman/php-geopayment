<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment\Provider;

use Longman\GeoPayment\Logger;
use Longman\GeoPayment\Options;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Abstract Provider class
 *
 */
abstract class AbstractProvider
{

    /**
     * Provider name
     *
     * @var string
     */
    protected $name;

    /**
     * Mode (depends on bank)
     *
     * @var string
     */
    protected $mode;

    /**
     * @var \Longman\GeoPayment\Logger
     */
    protected $logger;

    /**
     * @var \Longman\GeoPayment\Options
     */
    protected $options;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * Custom parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * Constructor
     *
     * @param \Longman\GeoPayment\Options                $options   Options object
     * @param \Symfony\Component\HttpFoundation\Request  $request   Request object
     * @param \Longman\GeoPayment\Logger                 $logger    Logger object
     *
     * @return void
     */
    public function __construct(Options $options, Request $request, Logger $logger)
    {
        $this->options = $options;
        $this->request = $request;
        $this->logger  = $logger;
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add custom parameter to url
     *
     * @param string $name  Parameter name
     * @param mixed  $value Parameter value
     *
     * @return \Longman\GeoPayment\Provider\Pay\AbstractProvider
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get parameter from params, otherwise get request field
     *
     * @param string $name    Parameter name
     * @param mixed  $default Parameter default value
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        $name = str_replace('.', '_', $name);
        return $this->request->get($name, $default);
    }

    /**
     * Redirect to url
     *
     * @param string $url Url to redirect
     *
     * @return void
     */
    public function redirect($url = null)
    {
        if (is_null($url)) {
            $url = $payment->getPaymentUrl();
        }

        RedirectResponse::create($url)->send();

        exit();
    }

    /**
     * Set mode
     *
     * @param string $mode
     *
     * @return \Longman\GeoPayment\Provider\Pay\AbstractProvider
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Check HTTP Authorization
     *
     * @return void
     */
    public function checkHttpAuth()
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="' . $this->options->get('shop_name', 'Online Shop') . '"');
            header('HTTP/1.0 401 Unauthorized');
            $this->logger->warning('HTTP Authorization cancelled');
            echo 'Access denied';
            exit;
        } else {
            if ($_SERVER['PHP_AUTH_USER'] != $this->options->get('http_auth_user')
                || $_SERVER['PHP_AUTH_PW'] != $this->options->get('http_auth_pass')
            ) {
                $this->logger->warning('HTTP Authorization error: wrong username or password');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Access denied';
                exit;
            }
        }
    }


    /**
     * Check if IP Address Allowed
     *
     * @return void
     */
    public function checkIpAllowed()
    {
        $ip_list = $this->options->get('allowed_ips');
        if ($ip_list) {
            $client_ip = $this->request->getClientIp();
            $status = Ip::match($client_ip, explode(',', $ip_list));
            if (!$status) {
                $this->logger->warning('IP Not Allowed');
                $response = Response::create('Access denied for IP: '.$client_ip, 403);
                $response->send();
                exit();
            }
        }
    }

    /**
     * Parse XML string
     *
     * @param string $xml
     *
     * @return \SimpleXMLElement
     *
     * @throws \UnexpectedValueException
     */
    protected function parseXml($xml)
    {
        libxml_use_internal_errors(true);
        $object = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        if ($errors) {
            $err = [];
            foreach ($errors as $err_obj) {
                $err[] = $err_obj->message;
            }
            $err_str = implode(', ', $err);

            $this->logger->error('XML parsing error. ' . $err_str);
            throw new UnexpectedValueException('XML string is invalid. libXML Errors: ' . $err_str);
        }

        return $object;
    }

    /**
     * Perform amount to minor (eg. cents)
     *
     * @param float $amount Amount in major units (eg. dollars)
     *
     * @return int
     */
    public function amtToMinor($amount)
    {
        if ($amount == 0) {
            return 0;
        }
        $amount = str_replace([',', ' '], ['.', ''], trim($amount));
        $amount *= 100;

        return intval($amount);
    }


    /**
     * Perform amount to major (dollars)
     *
     * @param int $amount Amount in minor units (eg. cents)
     *
     * @return float
     */
    public function amtToMajor($amount)
    {
        if ($amount == 0) {
            return $this->format($amount);
        }
        $amount = $amount / 100;
        return $this->format($amount);
    }



    /**
     * Format amount
     *
     * @param mixed $amount   Amount
     * @param int   $decimals Sets the number of decimal points.
     *
     * @return float
     */
    public function format($amount = 0, $decimals = 2)
    {
        $amount = str_replace([',', ' '], ['.', ''], trim($amount));
        $amount = (float) $amount;
        $amount = ($amount * 100) / 100;
        $amount = number_format($amount, $decimals, '.', '');
        return (float) $amount;
    }

    /**
     * Get request url
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->request->getUri();
    }

    /**
     * Encode url string
     *
     * @param string $str
     *
     * @return string Encoded string
     */
    protected function encode($str)
    {
        return rawurlencode($str);
    }
}
