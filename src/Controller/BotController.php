<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WowApps\SlackBundle\DTO\Attachment;
use WowApps\SlackBundle\DTO\SlackMessage;
use WowApps\SlackBundle\Service\SlackBot;

class BotController extends AbstractController
{
    private $bot;
    private $rapidViewId;
    private $sprintId;
    private $postTime;
    private $token;

    /**
     * @Route("/")
     * @return Response
     */
    public function index(): Response
    {
        return new Response(
            '<html><body>SERVER WORKS!!</body></html>'
        );
    }

    /**
     * BotController constructor.
     *
     * @param $bot
     */
    public function __construct(SlackBot $bot)
    {
        $this->bot = $bot;
        $this->rapidViewId = 303;
        $this->sprintId = 906;
    }

    /**
     * @Route("/set_rapid_view")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setRapidViewId(Request $request): Response
    {
        $text = $request->get('text');
        $this->validate($text, 'integer');
        $id = (int) $text;

        return new Response('Значение *view_id* установлено');
    }

    /**
     * @Route("/set_sprint")
     *
     * @return Response
     */
    public function setSprintId(Request $request): Response
    {
        $text = $request->get('text');
        $this->validate($text, 'integer');
        $id = (int) $text;

        return new Response('Значение *sprint_id* установлено');
    }

    /**
     * @Route("/set_post_time")
     *
     * @return Response
     */
    public function setPostTime(Request $request): Response
    {
        $time = $request->get('text');
        $this->validate($time, 'time');

        return new Response('Значение *post_time* установлено');
    }

    public function postBurndown(string $channel = 'my-test'): void
    {
        $imageUrl = $this->getImage();
        $chart = new Attachment();
        $chart->setImageUrl($imageUrl);
        $message = new SlackMessage('Cвежий Burndown Chart');
        $message->setChannel($channel);
        $message->appendAttachment($chart);
        $this->bot->send($message);
    }

    private function getImage(): string
    {
        //        TODO this
        return '';
    }

    private function getData(): string
    {
        $url = sprintf('https://devjira.skyeng.ru/rest/greenhopper/1.0/rapid/charts/scopechangeburndownchart?rapidViewId=%d&sprintId=%d',
            $this->rapidViewId, $this->sprintId);

        return '';
    }

    private function validate($var, $type): ?Response
    {
        if ($type === 'integer' && !preg_match('/^\d+$/', $var)) {
            return new Response('Неправильно. Для параметра *id* необходимо вводить целое число');

        }

        if ($type === 'time' && !preg_match('/^\d{2}:\d{2}(:?:\d{2})?$/', $var)) {
            return new Response('Неправильно. Для параметра *time* необходимо вводить время (hh:mm)');
        }
    }
}
