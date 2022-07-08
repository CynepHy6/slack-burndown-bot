<?php


namespace App;

use App\Controller\BotController;

class Utils
{
    /**
     * @param string $text
     * @param string $type
     *
     * @return bool
     */
    public static function validate(string $text, string $type): bool
    {
        if ($type === BotController::SPRINT_ID || $type === BotController::RAPID_VIEW_ID) {
            return (bool) preg_match('/^\d+$/', $text);
        }

        if ($type === BotController::POST_TIME && $text !== '') {
            return (bool) preg_match('/^(\d{2}:\d{2})$/', $text);
        }
        return true;
    }
}
