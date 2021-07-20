<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use winwin\apisix\cli\ArrayHelper;

class RoutesCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('routes');
        $this->addArgument('id', InputArgument::OPTIONAL, 'show route by id');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Delete route');
        $this->setDescription('list routes');
    }

    protected function handle(): void
    {
        $routeId = $this->input->getArgument('id');
        if ($routeId) {
            if ($this->input->getOption('delete')) {
                $this->deleteRoute($routeId);
            } else {
                $this->showRoute($routeId);
            }

            return;
        }
        $table = $this->createTable(['ID', 'Upstream', 'Hosts', 'Methods', 'Uris']);
        $data = $this->getAdminClient()->get('routes');
        foreach ($data['nodes'] ?? [] as $node) {
            $route = $node['value'];
            $table->addRow([
                $this->getRouteId($node),
                $route['upstream_id'] ?? '',
                $this->getItem($route, 'host'),
                $this->getItem($route, 'method'),
                $this->getItem($route, 'uri'),
            ]);
        }
        $table->render();
    }

    private function getItem(array $route, string $key, string $pluralKey = null): string
    {
        if (!isset($pluralKey)) {
            $pluralKey = $key.'s';
        }
        if (!empty($route[$pluralKey])) {
            return json_encode($route[$pluralKey], JSON_UNESCAPED_SLASHES);
        }

        return $route[$key] ?? '';
    }

    private function showRoute(string $routeId): void
    {
        $node = $this->getAdminClient()->get('routes/'.$routeId);
        if ('json' === $this->input->getOption('format')) {
            $this->output->write(json_encode([
                'routes' => [$routeId => $node],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return;
        }
        $table = $this->createTable(['Name', 'Value']);
        foreach (ArrayHelper::flatten($node['value']) as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->render();
    }

    protected function getRouteId(array $node): string
    {
        return $node['value']['id'] ?? (substr($node['key'], strlen('/apisix/routes/')));
    }

    private function deleteRoute(string $routeId): void
    {
        $this->getAdminClient()->delete('routes/'.$routeId);
        $this->output->writeln("<info>Delete route $routeId successfully!</info>");
    }
}
