<?php

namespace winwin\apisix\cli;

class ArrayHelper
{
    public static function flatten(array $upstream, string $prefix = null): array
    {
        $values = [];
        foreach ($upstream as $key => $value) {
            $name = ($prefix ? $prefix.'.' : '').$key;
            if (is_array($value) && !isset($value[0])) {
                $values[] = self::flatten($value, $name);
            } else {
                $values[] = [
                    $name => is_array($value)
                        ? json_encode($value, JSON_UNESCAPED_SLASHES)
                        : $value,
                ];
            }
        }

        return empty($values) ? [] : array_merge(...$values);
    }
}
