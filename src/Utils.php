<?php


namespace App;

use Symfony\Component\HttpFoundation\Response;

class Utils
{
    public static function flatten(array $array, $key = ''): array
    {
        $return = [];
        if ($key === '') {
            array_walk_recursive($array, static function ($a) use (&$return) {
                $return[] = $a;
            });
        } else {
            array_walk_recursive($array, static function ($a, $k) use (&$return, &$key) {
                if ($k === $key) {
                    $return[] = $a;
                }
            });
        }
        return $return;
    }

    public static function log(...$a): void
    {
        echo json_encode($a, JSON_PRETTY_PRINT);
    }

    /**
     * @param $var string
     * @param $type string
     *
     * @return bool|Response
     */
    public static function validate($var, $type)
    {
        if ($type === 'integer' && !preg_match('/^\d+$/', $var)) {
            return new Response('Неправильно. Для параметра *id* необходимо вводить целое число');

        }

        if ($type === 'time' && !preg_match('/^\d{2}:\d{2}(:?:\d{2})?$/', $var)) {
            return new Response('Неправильно. Для параметра *time* необходимо вводить время (hh:mm)');
        }
        return true;
    }
}
