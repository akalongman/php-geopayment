<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Longman\GeoPayment\Provider\Card\Cartu;

use Longman\GeoPayment\Provider\AbstractXMLResponse;

class XMLResponse extends AbstractXMLResponse
{

    public function error($data)
    {
        $this->content = $this->getCheckErrorBody($code, $desc);
        return $this;
    }

    public function success($data = [])
    {
        $this->content = $this->getCheckSuccessBody($data);
        return $this;
    }

    protected function getCheckSuccessBody($data)
    {
        $TransactionId = $this->clean($data['TransactionId'], 32);
        $PaymentId     = $this->clean($data['PaymentId'], 12);

        $content = <<<XML
<ConfirmResponse>
    <TransactionId>{$TransactionId}</TransactionId>
    <PaymentId>{$PaymentId}</PaymentId>
    <Status>ACCEPTED</Status>
</ConfirmResponse>
XML;
        return $content;
    }

    protected function getCheckErrorBody($data)
    {
        $TransactionId = $this->clean($data['TransactionId'], 32);
        $PaymentId     = $this->clean($data['PaymentId'], 12);

        $content = <<<XML
<ConfirmResponse>
    <TransactionId>{$TransactionId}</TransactionId>
    <PaymentId>{$PaymentId}</PaymentId>
    <Status>DECLINED</Status>
</ConfirmResponse>
XML;

        return $content;
    }
}
