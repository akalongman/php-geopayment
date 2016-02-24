<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment\Provider\Pay\Cartu;

use Carbon\Carbon;
use Longman\GeoPayment\Options;
use Longman\GeoPayment\Provider\Pay\AbstractProvider;
use Longman\GeoPayment\Provider\Pay\Cartu\XMLResponse;
use Symfony\Component\HttpFoundation\Request;

class Provider extends AbstractProvider
{
    protected $name = 'cartu';

    protected $mode = 'redirect'; // redirect, response

    protected $xml_request = '';

    const DEF_PAYMENT_URL = 'https://e-commerce.cartubank.ge/servlet/Process3DSServlet/3dsproxy_init.jsp';
    const DEF_CURRENCY    = '840';
    const DEF_LANG        = '01';

    public function setMode($mode)
    {
        parent::setMode($mode);

        if ($mode == 'response') {
            $ConfirmRequest = $this->request->get('ConfirmRequest');
            if ($ConfirmRequest) {
                $this->xml_request = $this->parseXML($ConfirmRequest);
            }
        }
        return $this;
    }

    public function getTransactionId()
    {
        return (string) $this->xml_request->TransactionId;
    }

    public function getPaymentId()
    {
        return (string) $this->xml_request->PaymentId;
    }

    public function getPaymentDate()
    {
        $date = (string) $this->xml_request->PaymentDate;
        $date = Carbon::createFromFormat('d.m.Y H:i:s', $date);
        return $date;
    }

    public function getAmount()
    {
        return (string) $this->xml_request->Amount;
    }

    public function getCardType()
    {
        return (string) $this->xml_request->CardType;
    }

    public function getReason()
    {
        return (string) $this->xml_request->Reason;
    }

    public function getStatus()
    {
        return (string) $this->xml_request->Status;
    }

    public function checkSignature()
    {
        $signature = $this->request->get('signature');
        if (!$signature) {
            $this->sendErrorResponse('Signature is missing!');
        }

        $ConfirmRequest = $this->request->get('ConfirmRequest');

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

        $gateway .= '?CountryCode=' . $this->encode($this->options->get('CountryCode', self::DEF_LANG));

        $gateway .= '&CurrencyCode=' . $this->encode($this->options->get('CurrencyCode', self::DEF_CURRENCY));

        $gateway .= '&MerchantName=' . $this->encode($this->options->get('MerchantName'));
        $gateway .= '&MerchantURL=' . $this->encode($this->options->get('MerchantURL'));
        $gateway .= '&MerchantCity=' . $this->encode($this->options->get('MerchantCity'));
        $gateway .= '&MerchantID=' . $this->encode($this->options->get('MerchantID'));
        $gateway .= '&xDDDSProxy.Language=' . $this->encode($this->options->get('xDDDSProxy.Language'));

        foreach ($this->params as $key => $value) {
            $gateway .= '&' . $key . '=' . $this->encode($value);
        }

        return $gateway;
    }

    public function sendSuccessResponse($data = [])
    {
        $response = new XMLResponse($this->mode);

        if (empty($data['TransactionId'])) {
            $this->logger->debug('TransactionId not defined', ['data' => $data]);
            throw new InvalidArgumentException('TransactionId not defined');
        }

        if (empty($data['PaymentId'])) {
            $this->logger->debug('PaymentId not defined', ['data' => $data]);
            throw new InvalidArgumentException('PaymentId not defined');
        }

        $this->logger->debug('sendSuccessResponse: ' . $this->mode, ['data' => $data]);

        $response->success($data)->send();
    }

    public function sendErrorResponse($data = [])
    {
        $this->logger->debug('sendErrorResponse: ' . $this->mode, ['data' => $data]);
        $response = new XMLResponse($this->mode);
        $response->error($data)->send();
    }
}
