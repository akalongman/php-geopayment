<?php
/*
 * This file is part of the GeoPayment package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\GeoPayment;

use Longman\GeoPayment\Contracts\Options as OptionsContract;

/**
 * Class for holding options data
 *
 */
class Options implements OptionsContract
{

    /**
     * @var array
     */
    protected $data;

    /**
     * Constructor
     *
     * @param array $data Options
     *
     * @return void
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Get option
     *
     * @param string $name    Option name
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }

        return $default;
    }

    /**
     * Set option
     *
     * @param string $name  Option name
     * @param array  $value Option value
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }
}
