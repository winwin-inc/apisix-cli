<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use winwin\apisix\cli\ArrayHelper;

class ConsumersCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('consumers');
        $this->addArgument('consumer', InputArgument::OPTIONAL, 'show consumer by name');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Delete consumer');
        $this->setDescription('list consumers');
    }

    protected function handle(): void
    {
        $consumer = $this->input->getArgument('consumer');
        if ($consumer) {
            if ($this->input->getOption('delete')) {
                $this->deleteConsumer($consumer);
            } else {
                $this->showConsumer($consumer);
            }

            return;
        }
        $table = $this->createTable(['Consumer', 'Plugins']);
        $data = $this->getAdminClient()->get('consumers');
        foreach ($data['nodes'] ?? [] as $node) {
            $consumer = $node['value'];
            $table->addRow([
                $consumer['username'],
                implode(',', array_keys($consumer['plugins'])),
            ]);
        }
        $table->render();
    }

    private function deleteConsumer(string $consumer): void
    {
        $this->getAdminClient()->delete('consumers/'.$consumer);
        $this->output->writeln("<info>Delete consumer $consumer successfully!</info>");
    }

    private function showConsumer(string $consumer): void
    {
        $node = $this->getAdminClient()->get('consumers/'.$consumer);
        if ('json' === $this->input->getOption('format')) {
            $this->output->write(json_encode([
                'consumers' => [$consumer => $node],
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
}
