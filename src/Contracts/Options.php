<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment\Contracts;

interface Options
{

    /**
     * Constructor
     *
     * @param array $data Options
     *
     * @return void
     */
    public function __construct($data = []);

    /**
     * Get option
     *
     * @param string $name    Option name
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Set option
     *
     * @param string $name  Option name
     * @param array  $value Option value
     *
     * @return void
     */
    public function set($name, $value);
}
