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

use Dotenv\Dotenv;
use InvalidArgumentException;
use Longman\GeoPayment\Contracts\Options as OptionsContract;
use Longman\GeoPayment\Contracts\Logger as LoggerContract;
use Longman\GeoPayment\Provider\Pay\AbstractProvider;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger as MonologLogger;

/**
 * Universal payment class.
 *
 */
class Payment
{

    /**
     * @var \Longman\GeoPayment\Provider\Pay\AbstractProvider
     */
    protected $provider;

    /**
     * Payment type
     *
     * @var string
     */
    protected $type = 'pay';

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
     * @var bool
     */
    protected $debug;

    /**
     * Constructor
     *
     * @param string $provider  Provider name
     * @param array  $options   Options
     *
     * @return void
     */
    public function __construct($provider = null, array $options = [])
    {
        $this->createOptionsInstance($options);

        $this->createRequestInstance();

        if ($config_path = $this->options->get('config_path')) {
            $this->setConfigFile($config_path);
        }

        if ($log_path = $this->options->get('log_path')) {
            $this->createLoggerInstance($log_path, $provider);
        }

        if ($provider) {
            $this->createProviderInstance($provider);
        }

    }

    /**
     * Create Request instance
     *
     * @return \Longman\GeoPayment\Payment
     */
    protected function createRequestInstance()
    {
        $this->setRequestInstance(Request::createFromGlobals());
        return $this;
    }

    /**
     * Set Request instance
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Request instance
     *
     * @return \Longman\GeoPayment\Payment
     */
    public function setRequestInstance(Request $request)
    {
        $this->request = $request;
        return $this;
    }


    /**
     * Create Options instance
     *
     * @param array $options Options array
     *
     * @return \Longman\GeoPayment\Payment
     */
    protected function createOptionsInstance(array $options)
    {
        $this->setOptionsInstance(new Options($options));
        return $this;
    }

    /**
     * Set Options instance
     *
     * @param \Longman\GeoPayment\Contracts\Options $options Options instance
     *
     * @return \Longman\GeoPayment\Payment
     */
    public function setOptionsInstance(OptionsContract $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Configure logger object
     *
     * @param string $path      Path to log folder
     * @param string $provider  Provider name
     *
     * @return \Longman\GeoPayment\Payment
     */
    protected function createLoggerInstance($path, $provider = null)
    {
        if (!$provider) {
            $provider = 'default';
        }

        $log_path     = $path . '/' . $provider . '.log';
        $log_level    = $this->options->get('log_level', 'debug');

        $logger = new Logger(new MonologLogger($log_level));

        $logger->getMonolog()->pushProcessor(function ($record) {
            $record['ip'] = $this->request->server->get('REMOTE_ADDR');
            $record['extra']['GET'] = $this->request->query->all();
            $record['extra']['POST'] = $this->request->request->all();
            return $record;
        });

        $logger->useDailyFiles($log_path, 0);

        $this->setLoggerInstance($logger);

        return $this;
    }

    /**
     * Set Logger instance
     *
     * @param \Longman\GeoPayment\Contracts\Logger $logger Logger instance
     *
     * @return \Longman\GeoPayment\Payment
     */
    public function setLoggerInstance(LoggerContract $logger)
    {
        $this->logger = $logger;
        return $this;
    }


    /**
     * Parse config dotfile
     *
     * @param string $path Config file path
     *
     * @return \Longman\GeoPayment\Payment
     */
    public function setConfigFile($path)
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException('Config path "' . $path . '" is not a file');
        }

        $folder = dirname($path);
        $file   = pathinfo($path, PATHINFO_BASENAME);
        $dotenv = new Dotenv($folder, $file);
        $dotenv->load();
        return $this;
    }

    /**
     * Create provider instance
     *
     * @param string $provider Provider name
     *
     * @return \Longman\GeoPayment\Payment
     *
     * @throws \InvalidArgumentException When the provider not found
     */
    protected function createProviderInstance($provider)
    {
        $class = '\Longman\GeoPayment\Provider\Pay\\' . ucfirst($provider) . '\Provider';
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Provider "' . $provider . '" not found!');
        }

        $this->setProviderInstance(new $class($this->options, $this->request, $this->logger));

        return $this;
    }

    /**
     * Set provider instance
     *
     * @param \Longman\GeoPayment\Provider\Pay\AbstractProvider $provider Provider instance
     *
     * @return \Longman\GeoPayment\Payment
     */
    public function setProviderInstance(AbstractProvider $provider)
    {
        $this->provider = $provider;
        return $this;
    }


    /**
     * Get Option value
     *
     * @param string $name     Option name
     * @param mixed  $default  Default value
     *
     * @return mixed
     */
    public function getOption($name = null, $default = null)
    {
        return $this->options->get($name, $default);
    }


    /**
     * Get Options object
     *
     * @return mixed|\Longman\GeoPayment\Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get request instance
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get provider instance
     *
     * @return \Longman\GeoPayment\Provider\Pay\AbstractProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Pass function calls to provider instance
     *
     * @return mixed
     *
     * @throws \RuntimeException When the provider can not call method
     */
    public function __call($method, $args)
    {
        if (!$this->provider instanceof AbstractProvider) {
            throw new RuntimeException('Provider not initialized');
        }

        if (!is_callable([$this->provider, $method])) {
            throw new RuntimeException('Provider "' . $this->provider->getName() . '" does not have callable method "' . $method . '"');
        }

        return call_user_func_array([$this->provider, $method], $args);
    }
}
