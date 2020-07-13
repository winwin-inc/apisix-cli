<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpstreamsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('upstreams');
        $this->addArgument('id', InputArgument::OPTIONAL, 'show upstream by id');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Delete upstream');
        $this->setDescription('list upstream');
    }

    protected function handle(): void
    {
        $upstreamId = $this->input->getArgument('id');
        if ($upstreamId) {
            if ($this->input->getOption('delete')) {
                $this->deleteUpstream($upstreamId);
            } else {
                $this->showUpstreamInfo($upstreamId);
            }

            return;
        }
        $table = $this->createTable(['ID', 'Nodes', 'Type', 'Retry']);
        $data = $this->getAdminClient()->get('upstreams');
        foreach ($data['nodes'] ?? [] as $node) {
            $upstream = $node['value'];
            $table->addRow([
                $this->getUpstreamId($node),
                implode(',', array_keys($upstream['nodes'] ?? [])),
                $upstream['type'] ?? '',
                $upstream['retry'] ?? 0,
            ]);
        }
        $table->render();
    }

    private function showUpstreamInfo(string $upstreamId): void
    {
        $node = $this->getAdminClient()->get('upstreams/'.$upstreamId);
        if ('json' === $this->input->getOption('format')) {
            $this->output->write(json_encode([
                'upstreams' => [$upstreamId => $node['value']],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return;
        }
        $table = $this->createTable(['Name', 'Value']);
        $table->addRow(['ID', $upstreamId]);
        foreach ($this->flatten($node['value']) as $key => $value) {
            if ('id' === $key) {
                continue;
            }
            $table->addRow([$key, $value]);
        }
        $table->render();
    }

    private function flatten(array $upstream, string $prefix = null): array
    {
        $values = [];
        foreach ($upstream as $key => $value) {
            $name = ($prefix ? $prefix.'.' : '').$key;
            if (is_array($value) && !isset($value[0])) {
                $values = array_merge($values, $this->flatten($value, $name));
            } else {
                $values[$name] = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : $value;
            }
        }

        return $values;
    }

    protected function getUpstreamId(array $node)
    {
        return $node['value']['id'] ?? (substr($node['key'], strlen('/apisix/upstreams/')));
    }

    private function deleteUpstream(string $upstreamId)
    {
        $this->getAdminClient()->delete('upstreams/'.$upstreamId);
        $this->output->writeln("<info>Delete upstream $upstreamId successfully!</info>");
    }
}
