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
        $this->addOption('remove-node', null, InputOption::VALUE_REQUIRED, 'Remove node from upstream');
        $this->addOption('add-node', null, InputOption::VALUE_REQUIRED, 'Remove node from upstream');
        $this->setDescription('list upstream');
    }

    protected function handle(): void
    {
        $upstreamId = $this->input->getArgument('id');
        if ($upstreamId) {
            if ($this->input->getOption('delete')) {
                $this->deleteUpstream($upstreamId);
            } elseif ($this->input->getOption('remove-node')) {
                $this->removeNode($upstreamId, $this->input->getOption('remove-node'));
            } elseif ($this->input->getOption('add-node')) {
                $this->addNode($upstreamId, $this->input->getOption('add-node'));
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
            if (in_array($key, ['update_time', 'create_time'], true)) {
                $value = date('Y-m-d H:i:s', $value);
            }
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

    private function removeNode(string $upstreamId, string $node): void
    {
        $upstream = $this->getAdminClient()->get('upstreams/'.$upstreamId);
        if (!isset($upstream['value']['nodes'][$node])) {
            $this->output->writeln("<error>Upstream $upstreamId does not has node $node, node list "
                .implode(',', array_keys($upstream['value']['nodes'])).'</error>');
        }
        $upstream['value']['nodes'][$node] = null;
        $this->getAdminClient()->patchJson('upstreams/'.$upstreamId, [
            'nodes' => $upstream['value']['nodes'],
        ]);
        $this->output->writeln("<info>Remove node $node from upstream $upstreamId successfully!</info>");
    }

    private function addNode(string $upstreamId, string $node)
    {
        $weight = 100;
        if (false !== strpos($node, '=')) {
            [$node, $weight] = explode('=', $node, 2);
        }
        $upstream = $this->getAdminClient()->get('upstreams/'.$upstreamId);
        if (isset($upstream['value']['nodes'][$node])) {
            $this->output->writeln("<error>Upstream $upstreamId already has node $node, node list "
                .implode(',', array_keys($upstream['value']['nodes'])).'</error>');
        }
        $upstream['value']['nodes'][$node] = $weight;
        $this->getAdminClient()->patchJson('upstreams/'.$upstreamId, [
            'nodes' => $upstream['value']['nodes'],
        ]);
        $this->output->writeln("<info>Add node $node to upstream $upstreamId successfully!</info>");
    }
}
