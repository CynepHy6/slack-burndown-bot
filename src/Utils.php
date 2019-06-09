<?php


namespace App;

use App\Controller\BotController;

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
     * @param string $text
     * @param string $type
     *
     * @return bool
     */
    public static function validate(string $text, string $type): bool
    {
        if (($type === BotController::SPRINT_ID || $type === BotController::RAPID_VIEW_ID) && !preg_match('/^\d+$/',
                $text)) {
            return false;
        }

        if ($type === BotController::POST_TIME && !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $text)) {
            return false;
        }
        return true;
    }
}
