<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
                $this->showRouteInfo($routeId);
            }

            return;
        }
        $table = $this->createTable(['ID', 'Hosts', 'Methods', 'Uris']);
        $data = $this->getAdminClient()->get('routes');
        foreach ($data['nodes'] ?? [] as $node) {
            $route = $node['value'];
            $table->addRow([
                $this->getRouteId($node),
                $this->getItem($route, 'host'),
                $this->getItem($route, 'method'),
                $this->getItem($route, 'uri'),
            ]);
        }
        $table->render();
    }

    private function getItem(array $route, string $key, string $pluralKey = null)
    {
        if (!isset($pluralKey)) {
            $pluralKey = $key.'s';
        }

        return json_encode(
            $route[$pluralKey] ?? (isset($route[$key]) ? [$route[$key]] : []),
            JSON_UNESCAPED_SLASHES
        );
    }

    private function showRouteInfo(string $routeId): void
    {
        $node = $this->getAdminClient()->get('routes/'.$routeId);
        if ('json' === $this->input->getOption('format')) {
            $this->output->write(json_encode([
                'routes' => [$routeId => $node],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return;
        }
        $table = $this->createTable(['Name', 'Value']);
        $table->addRow(['ID', $routeId]);
        foreach ($this->flatten($node['value']) as $key => $value) {
            if ('id' === $key) {
                continue;
            }
            $table->addRow([$key, $value]);
        }
        $table->render();
    }

    private function flatten(array $route, string $prefix = null): array
    {
        $values = [];
        foreach ($route as $key => $value) {
            $name = ($prefix ? $prefix.'.' : '').$key;
            if (is_array($value) && !isset($value[0])) {
                $values = array_merge($values, $this->flatten($value, $name));
            } else {
                $values[$name] = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : $value;
            }
        }

        return $values;
    }

    protected function getRouteId(array $node)
    {
        return $node['value']['id'] ?? (substr($node['key'], strlen('/apisix/routes/')));
    }

    private function deleteRoute(string $routeId)
    {
        $this->getAdminClient()->delete('routes/'.$routeId);
        $this->output->writeln("<info>Delete route $routeId successfully!</info>");
    }
}
