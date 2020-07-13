<?php

namespace winwin\apisix\cli\commands;

use Symfony\Component\Console\Input\InputOption;
use winwin\apisix\cli\exception\ResourceNotFoundException;

class ApplyCommand extends AbstractCommand
{
    private const ID_KEY = [
        'consumers' => 'username',
    ];

    protected function configure(): void
    {
        parent::configure();
        $this->setName('apply');
        $this->addArgument('json', InputOption::VALUE_REQUIRED, 'configuration');
    }

    protected function handle(): void
    {
        $data = $this->readJson($this->input->getArgument('json'));
        foreach ($data as $category => $items) {
            foreach ($items as $id => $item) {
                try {
                    $node = $this->getAdminClient()->get($category.'/'.$id);
                    $result = $this->patch($node['value'], $item);
                    $this->getAdminClient()->patchJson($category.'/'.$id, $result);
                } catch (ResourceNotFoundException $e) {
                    $item[self::ID_KEY[$category] ?? 'id'] = $id;
                    $this->getAdminClient()->putJson($category, $item);
                }
            }
        }
    }

    private function patch(array $oldValue, array $current, $prefix = null): array
    {
        $result = [];
        foreach ($oldValue as $name => $item) {
            $key = ($prefix ? $prefix.'.' : '').$name;
            if (!isset($current[$name])) {
                $result[$name] = null;
            } else {
                if (is_array($item) && !isset($item[0])) {
                    if (!is_array($current[$name])) {
                        throw new \InvalidArgumentException($key.' should be an array, got '.$current[$name]);
                    }
                    $result[$name] = $this->patch($item, $current[$name], $key);
                } else {
                    $result[$name] = $current[$name];
                }
                unset($current[$name]);
            }
        }

        return array_merge($result, $current);
    }
}
