<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use winwin\apisix\cli\ArrayHelper;

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
                $this->showUpstream($upstreamId);
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

    private function showUpstream(string $upstreamId): void
    {
        $node = $this->getAdminClient()->get('upstreams/'.$upstreamId);
        if ('json' === $this->input->getOption('format')) {
            $this->output->write(json_encode([
                'upstreams' => [$upstreamId => $node['value']],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return;
        }
        $table = $this->createTable(['Name', 'Value']);
        foreach (ArrayHelper::flatten($node['value']) as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->render();
    }

    protected function getUpstreamId(array $node): string
    {
        return $node['value']['id'] ?? (substr($node['key'], strlen('/apisix/upstreams/')));
    }

    private function deleteUpstream(string $upstreamId): void
    {
        $this->getAdminClient()->delete('upstreams/'.$upstreamId);
        $this->output->writeln("<info>Delete upstream $upstreamId successfully!</info>");
    }
}
