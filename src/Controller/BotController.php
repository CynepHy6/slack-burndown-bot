<?php


namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;
use WowApps\SlackBundle\DTO\Attachment;
use WowApps\SlackBundle\DTO\SlackMessage;
use WowApps\SlackBundle\Service\SlackBot;

class BotController
{
    private $bot;
    private $rapidViewId;
    private $sprintId;
    private $postTime;

    /**
     * @Route('/')
     */
    public function index()
    {
        return 'server works';
    }

    /**
     * BotController constructor.
     *
     * @param $bot
     */
    public function __construct(SlackBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * @param int $viewId
     */
    public function setRapidViewId(int $viewId): void
    {
        $this->rapidViewId = $viewId;
    }

    /**
     * @param int $sprintId
     */
    public function setSprintId($sprintId): void
    {
        $this->sprintId = $sprintId;
    }

    /**
     * @param string $postTime
     */
    public function setPostTime($postTime): void
    {
        $this->postTime = $postTime;
    }

    public function postBurndown(string $channel = 'my-test')
    {
        $imageUrl = $this->getImage();
        $chart = new Attachment();
        $chart->setImageUrl($imageUrl);
        $message = new SlackMessage('Cвежий Burndown Chart');
        $message->setChannel($channel);
        $message->appendAttachment($chart);
        $this->bot->send($message);
    }

    private function getImage()
    {
        //        TODO this
        return '';
    }

}
