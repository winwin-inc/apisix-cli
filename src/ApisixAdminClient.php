<?php

declare(strict_types=1);

namespace winwin\apisix\cli;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use winwin\apisix\cli\exception\BadResponseException;
use winwin\apisix\cli\exception\ResourceNotFoundException;

class ApisixAdminClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var bool
     */
    private $debug;
    /**
     * @var mixed
     */
    private $result;
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config, LoggerInterface $logger, bool $debug = false)
    {
        $this->config = $config;
        $this->setLogger($logger);
        $this->debug = $debug;
    }

    protected function getHttpClient(): ClientInterface
    {
        if (!$this->httpClient) {
            $handler = HandlerStack::create();
            if ($this->debug) {
                $handler->push(Middleware::log($this->logger, new MessageFormatter(MessageFormatter::DEBUG)));
            }
            $handler->push(Middleware::mapResponse(function (ResponseInterface $response) {
                $data = json_decode((string) $response->getBody(), true);
                if (404 === $response->getStatusCode()) {
                    throw new ResourceNotFoundException($data['cause'] ?? (string) $response->getBody());
                }
                if (!empty($data) && !isset($data['node'])) {
                    throw new BadResponseException("key 'node' not found in response: ".$response->getBody());
                }
                $this->result = $data['node'] ?? null;

                return $response;
            }));
            $this->httpClient = new Client([
                'handler' => $handler,
                'base_uri' => $this->config->getEndpoint().'/apisix/admin/',
                'headers' => [
                    'content-type' => 'application/json',
                    'x-api-key' => $this->config->getToken(),
                ],
            ]);
        }

        return $this->httpClient;
    }

    public function get($uri, array $query = [])
    {
        $this->getHttpClient()->request('GET', $uri, ['query' => $query]);

        return $this->getLastResult();
    }

    public function delete($uri, array $options = [])
    {
        $this->getHttpClient()->request('DELETE', $uri, $options);

        return $this->getLastResult();
    }

    public function putJson($uri, $data)
    {
        $this->getHttpClient()->request('PUT', $uri, [
            'json' => $data,
        ]);

        return $this->getLastResult();
    }

    public function patchJson($uri, $data)
    {
        $this->getHttpClient()->request('PATCH', $uri, [
            'json' => $data,
        ]);

        return $this->getLastResult();
    }

    private function getLastResult()
    {
        return $this->result;
    }
}
