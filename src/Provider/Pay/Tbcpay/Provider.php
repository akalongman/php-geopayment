<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment\Provider\Pay\Tbcpay;

use Longman\GeoPayment\Options;
use Longman\GeoPayment\Provider\AbstractProvider;
use Longman\GeoPayment\Provider\Pay\Tbcpay\XMLResponse;
use InvalidArgumentException;

class Provider extends AbstractProvider
{
    protected $name = 'tbcpay';

    protected $mode = 'check';


    public function sendSuccessResponse($data = [])
    {
        $response = new XMLResponse($this->mode);

        if ($this->mode == 'check') {
            if (empty($data)) {
                $this->logger->debug('extra data not defined', ['data' => $data]);
                throw new InvalidArgumentException('extra data not defined');
            }
        }

        $this->logger->debug('sendSuccessResponse: ' . $this->mode, ['data' => $data]);

        $response->success($data)->send();
    }

    public function sendErrorResponse($code = 1, $error = 'Unable to accept this payment')
    {
        $this->logger->debug('sendErrorResponse: "' . $error .'" with code: '.$code);
        $response = new XMLResponse($this->mode);
        $response->error($code, $error)->send();
    }
}
