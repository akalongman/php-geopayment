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

use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract XML response class
 *
 */
abstract class AbstractXMLResponse
{

    /**
     * Response mode (Depends on bank)
     *
     * @var string
     */
    protected $mode;

    /**
     * Response body
     *
     * @var string
     */
    protected $content;

    /**
     * Response object
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * Constructor
     *
     * @param string $mode Response mode
     *
     * @return void
     */
    public function __construct($mode)
    {
        $this->mode     = $mode;
        $this->response = new Response('', Response::HTTP_OK, ['content-type' => 'text/xml']);
    }

    /**
     * Get response body
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get response object
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Send response to browser
     *
     * @return void
     */
    public function send()
    {
        $this->response->setContent($this->content);

        $this->response->send();

        exit;
    }

    /**
     * Prepare response body
     *
     * @return string Response body
     */
    protected function prepare($string)
    {
        $string = preg_replace('#<!--.*?-->#', '', $string);
        return $string;
    }

    /**
     * Clean string
     *
     * @return string
     */
    protected function clean($var, $substr = null)
    {
        $var = htmlspecialchars($var);
        if ($substr) {
            $var = mb_substr($var, 0, $substr, 'UTF-8');
        }

        return $var;
    }
}
