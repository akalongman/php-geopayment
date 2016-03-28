<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment\Provider\Card\Bog;

use Longman\GeoPayment\Options;
use Longman\GeoPayment\Provider\AbstractProvider;
use Longman\GeoPayment\Provider\Card\Bog\XMLResponse;
use InvalidArgumentException;

class Provider extends AbstractProvider
{
    protected $name = 'bog';

    protected $mode = 'redirect';

    const DEF_PAYMENT_URL = 'https://sb3d.georgiancard.ge/payment/start.wsm';
    const DEF_CURRENCY    = '981';
    const DEF_LANG        = 'ka';

    public function setSuccessUrl($url)
    {
        $this->options->set('back_url_s', $url);
        return $this;
    }

    public function setFailUrl($url)
    {
        $this->options->set('back_url_f', $url);
        return $this;
    }

    public function checkSignature()
    {
        $signature = $this->request->get('signature');
        if (!$signature) {
            $this->sendErrorResponse('Signature is missing!');
        }

        $request_url = $url = $this->getRequestUrl();

        $url = preg_replace('#&signature=.*$#', '', $url);
        $url = rawurldecode($url);

        $cert_file = $this->options->get('cert_path');
        $cert      = file_get_contents($cert_file);
        $key       = openssl_pkey_get_public($cert);
        //dump(openssl_pkey_get_details($key));

        $signature = base64_decode($signature);
        $valid     = openssl_verify($url, $signature, $key, OPENSSL_ALGO_SHA1);

        if ($valid !== 1) {
            $this->sendErrorResponse('Signature is invalid!');
        }

        //$this->logger->info('url: '.$url);

        return $this;
    }

    public function getPaymentUrl()
    {
        $gateway = $this->options->get('payment_url', self::DEF_PAYMENT_URL);

        $gateway .= '?lang=' . $this->encode($this->options->get('lang', self::DEF_LANG));

        $gateway .= '&page_id=' . $this->encode($this->options->get('page_id'));
        $gateway .= '&merch_id=' . $this->encode($this->options->get('merchant_id'));

        $gateway .= '&back_url_s=' . $this->encode($this->options->get('back_url_s'));
        $gateway .= '&back_url_f=' . $this->encode($this->options->get('back_url_f'));

        foreach ($this->params as $key => $value) {
            $gateway .= '&o.' . $key . '=' . $this->encode($value);
        }

        return $gateway;
    }

    public function sendSuccessResponse($data = [])
    {
        $response = new XMLResponse($this->mode);

        if ($this->mode == 'check') {
            $data['account_id'] = $this->options->get('account_id');
            $data['currency']   = $this->options->get('currency', self::DEF_CURRENCY);

            if (empty($data['amount'])) {
                $this->logger->debug('amount not defined', ['data' => $data]);
                throw new InvalidArgumentException('amount not defined');
            }

            if (empty($data['short_desc'])) {
                $this->logger->debug('short_desc not defined', ['data' => $data]);
                throw new InvalidArgumentException('short_desc not defined');
            }

            if (empty($data['long_desc'])) {
                $this->logger->debug('long_desc not defined', ['data' => $data]);
                throw new InvalidArgumentException('long_desc not defined');
            }
        }

        $this->logger->debug('sendSuccessResponse: ' . $this->mode, ['data' => $data]);

        $response->success($data)->send();
    }

    public function sendErrorResponse($error = 'Unable to accept this payment')
    {
        $this->logger->debug('sendErrorResponse: ' . $error);
        $response = new XMLResponse($this->mode);
        $response->error(2, $error)->send();
    }
}
