<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use winwin\apisix\cli\ArrayHelper;

class CertsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('certs');
        $this->addArgument('id', InputArgument::OPTIONAL, 'cert id');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'delete cert');
        $this->addOption('key-file', null, InputOption::VALUE_REQUIRED, 'Key file');
        $this->addOption('cert-file', null, InputOption::VALUE_REQUIRED, 'Cert file');
        $this->addOption('sni', null, InputOption::VALUE_REQUIRED, 'sni');
        $this->setDescription('list https cert');
    }

    protected function handle(): void
    {
        $sni = $this->input->getOption('sni');
        $certId = $this->input->getArgument('id');
        if (null !== $sni) {
            $this->getAdminClient()->putJson('ssl/'.$certId, [
                'cert' => file_get_contents($this->input->getOption('cert-file')),
                'key' => file_get_contents($this->input->getOption('key-file')),
                'sni' => $sni,
            ]);
            if (0 === strpos($sni, '*.')) {
                $this->getAdminClient()->putJson('ssl/default-'.$certId, [
                    'cert' => file_get_contents($this->input->getOption('cert-file')),
                    'key' => file_get_contents($this->input->getOption('key-file')),
                    'sni' => substr($sni, 2),
                ]);
            }
        } elseif (true === $this->input->getOption('delete')) {
            $this->getAdminClient()->delete('ssl/'.$certId);
            $this->output->writeln("<info>Delete cert $certId successfully!</info>");
        } elseif ($certId) {
            $table = $this->createTable(['Name', 'Value']);
            $node = $this->getAdminClient()->get('ssl/'.$certId);
            if ('json' === $this->input->getOption('format')) {
                $this->output->write(json_encode([
                    'certs' => [$certId => $node],
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

                return;
            }

            foreach (ArrayHelper::flatten($node['value']) as $key => $value) {
                if (in_array($key, ['update_time', 'create_time'], true)) {
                    $value = date('Y-m-d H:i:s', $value);
                }
                $table->addRow([$key, $value]);
            }
            $table->render();
        } else {
            $table = $this->createTable(['Cert', 'Sni', 'Update Time']);
            $data = $this->getAdminClient()->get('ssl');
            foreach ($data['nodes'] ?? [] as $node) {
                $cert = $node['value'];
                $table->addRow([
                    $cert['id'],
                    $cert['sni'],
                    date('Y-m-d H:i:s', $cert['update_time']),
                ]);
            }
            $table->render();
        }
    }
}
