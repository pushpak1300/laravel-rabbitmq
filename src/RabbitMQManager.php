<?php

namespace Kunnu\RabbitMQ;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use Illuminate\Contracts\Container\Container;
use PhpAmqpLib\Connection\AbstractConnection;

class RabbitMQManager
{
    /**
     * Configuration key.
     *
     * @var string
     */
    const CONFIG_KEY = 'rabbitmq';

    /**
     * IoC Container/Application.
     *
     * @var Container $app
     */
    protected Container $app;

    /**
     * Configuration repository.
     *
     * @var Repository $config
     */
    protected Repository $config;

    /**
     * Connection pool.
     *
     * @var Collection $connections
     */
    protected Collection $connections;

    /**
     * Create a new RabbitMQManager instance.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->config = $this->app->get('config');
        $this->connections = new Collection([]);
    }

    /**
     * Resolve connection instance by name.
     *
     * @param string|null $name
     * @param ConnectionConfig|null $config
     * @return AbstractConnection
     */
    public function resolveConnection(?string $name = null, ?ConnectionConfig $config = null): AbstractConnection
    {
        $name = $name ?? $this->resolveDefaultConfigName();

        if (!$this->connections->has($name)) {
            $this->connections->put(
                $name,
                $this->makeConnection($config ?? $this->resolveConfig($name))
            );
        }

        return $this->connections->get($name);
    }

    /**
     * Resolve connection configuration.
     *
     * @return ConnectionConfig
     */
    public function resolveConfig(string $connectionName): ConnectionConfig
    {
        $configKey = self::CONFIG_KEY;
        $connectionKey = "{$configKey}.connections.{$connectionName}";
        return new ConnectionConfig($this->config->get($connectionKey, []));
    }

    /**
     * Create a new connection.
     *
     * @param ConnectionConfig $config
     *
     * @return AbstractConnection
     */
    protected function makeConnection(ConnectionConfig $config): AbstractConnection
    {
        return new AMQPSSLConnection(
            $config->getHost(),
            $config->getPort(),
            $config->getUser(),
            $config->getPassword(),
            $config->getVhost(),
            $config->getSSLOptions(),
            $config->getOptions(),
            $config->getSSLProtocol(),
        );
    }

    /**
     * Resolve default connection name.
     *
     * @return string|null
     */
    protected function resolveDefaultConfigName(): ?string
    {
        $configKey = self::CONFIG_KEY;
        return $this->config->get("{$configKey}.defaultConnection");
    }
}
