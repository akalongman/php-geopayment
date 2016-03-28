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

use Longman\GeoPayment\Provider\AbstractXMLResponse;

class XMLResponse extends AbstractXMLResponse
{

    public function error($code = 2, $desc = 'Unable to accept this payment')
    {
        $this->content = $this->getErrorBody($code, $desc);
        return $this;
    }

    public function success($data = [])
    {
        $this->content = $this->getSuccessBody($data);
        return $this;
    }

    protected function getSuccessBody(array $data = [])
    {
        $extra_str = '';
        foreach ($data as $k => $v) {
            $extra_str .= '<extra name="'.$this->clean($k).'">'.$this->clean($v).'</extra>'.PHP_EOL;
        }

        if ($extra_str) {
            $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <result>0</result>
    <info>
        {$extra_str}
    </info>
    <comment>OK</comment>
</response>
XML;
        } else {
            $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <result>0</result>
    <comment>OK</comment>
</response>
XML;
        }

        return $content;
    }

    protected function getErrorBody($code, $desc = 'Unable to accept this payment')
    {
        $desc = $this->clean($desc, 125);

        $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<response>
    <result>{$code}</result>
    <comment>{$desc}</comment>
</response>
XML;
        return $content;
    }
}
