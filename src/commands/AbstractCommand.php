<?php

declare(strict_types=1);

namespace winwin\apisix\cli\commands;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use winwin\apisix\cli\ApisixAdminClient;
use winwin\apisix\cli\Config;

abstract class AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ApisixAdminClient
     */
    private $adminClient;

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'output format, ascii|json');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'config file path');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'show debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->handle();

        return 0;
    }

    protected function getAdminClient(): ApisixAdminClient
    {
        if (!$this->adminClient) {
            $logger = new Logger('APISIX', [new ErrorLogHandler()]);
            $config = $this->input->getOption('config')
                ? Config::read($this->input->getOption('config'))
                : Config::getInstance();
            if (!$config->getEndpoint()) {
                throw new \InvalidArgumentException('API not config. Use config command set endpoint first.');
            }
            $this->adminClient = new ApisixAdminClient($config, $logger, $this->input->getOption('debug'));
        }

        return $this->adminClient;
    }

    protected function createTable(array $headers): Table
    {
        $table = new Table($this->output);
        $table->setHeaders($headers);

        return $table;
    }

    protected function readJson($data)
    {
        if ('-' === $data) {
            $data = file_get_contents('php://stdin');
        } elseif (!in_array($data[0], ['{', '['], true)) {
            $json = file_get_contents($data);
            if (!$json) {
                throw new \InvalidArgumentException("Cannot read file $data");
            }
            $data = $json;
        }

        return json_decode($data, true);
    }

    abstract protected function handle(): void;
}
