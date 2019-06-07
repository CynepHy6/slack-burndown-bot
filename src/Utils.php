<?php


namespace App;


class Utils
{
    public static function prepareBurndownData($data): array
    {
        $reader = new SprintJsonReader($data);
        $targetLine = [
            ['x' => $reader->sprint_start, 'y' => $reader->startEstimation()],
            ['x' => $reader->sprint_end, 'y' => 0],
        ];
        $loggedLine = [];
        $realLine = [];
        $lines = [
            ['name' => 'Target', 'color' => '#959595', 'data' => $targetLine],
            ['name' => 'Logged', 'color' => '#10cd10', 'data ' => $loggedLine],
            ['name ' => 'Real', 'color ' => '#cd1010', 'data' => $realLine],
        ];
        return $lines;

    }

    public function flatten(array $array)
    {
        $return = [];
        array_walk_recursive($array, static function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }
}
