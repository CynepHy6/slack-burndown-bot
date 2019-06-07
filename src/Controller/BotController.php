<?php


namespace App\Controller;


use App\Utils;
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
        $data = $this->getChartPath();
        return new Response(
            $data
        );
    }

    /**
     * BotController constructor.
     *
     * @param SlackBot $bot
     */
    public function __construct(SlackBot $bot)
    {
        $this->bot = $bot;
        $this->rapidViewId = '303';
        $this->sprintId = '906';
        $this->token = $_ENV['ATLASSIAN_API_TOKEN'];
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
        $this->rapidViewId = (int) $text;

        return new Response('Значение *view_id* установлено');
    }

    /**
     * @Route("/set_sprint")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setSprintId(Request $request): Response
    {
        $text = $request->get('text');
        $this->validate($text, 'integer');
        $this->sprintId = (int) $text;

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
        $this->postTime = $time;

        return new Response('Значение *post_time* установлено');
    }

    public function postBurndown(string $channel = 'my-test'): void
    {
        $imageUrl = $this->getChartPath();
        $chart = new Attachment();
        $chart->setImageUrl($imageUrl);
        $message = new SlackMessage('Cвежий Burndown Chart');
        $message->setChannel($channel);
        $message->appendAttachment($chart);
        $this->bot->send($message);
    }

    private function getChartPath(): string
    {
        $data = $this->getData();

        return '';
    }

    private function getData(): array
    {
        $params = http_build_query([
            'rapidViewId' => $this->rapidViewId,
            'sprintId'    => $this->sprintId,
        ]);
        $url = 'https://devjira.skyeng.ru/rest/greenhopper/1.0/rapid/charts/scopechangeburndownchart?' . $params;
        $key = $this->token;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n" .
                    "Authorization: Basic $key\r\n",
            ],
        ]);

        $jsonData = file_get_contents($url, false, $context);

        return Utils::prepareBurndownData($jsonData);
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
