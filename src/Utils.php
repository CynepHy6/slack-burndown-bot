<?php


namespace App;

use CpChart\Data;
use CpChart\Image;
use Symfony\Component\Config\Definition\Exception\Exception;

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
     * @param array $lines
     *
     * @return string path to image
     * @throws \Exception
     */

}
