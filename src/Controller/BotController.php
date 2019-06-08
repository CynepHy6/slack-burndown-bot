<?php


namespace App\Controller;


use App\BurnDownBuilder;
use App\SprintJsonReader;
use App\Utils;
use RuntimeException;
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
    private $serverUrl;


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
        $this->serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * @Route("/")
     * @return Response
     */
    public function index(): Response
    {
//        $res = $this->postBurndown('GK9T8DU7N');
//                $res = $this->createChart('.');
        return new Response(
            "<html><body></body></html>"
        );
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
        Utils::validate($text, 'integer');
        $this->rapidViewId = (int) $text;
        $channel = $request->get('channel_id');
        $this->postBurndown($channel);

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
        Utils::validate($text, 'integer');
        $this->sprintId = (int) $text;

        return new Response('Значение *sprint_id* установлено');
    }

    /**
     * @Route("/set_post_time")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setPostTime(Request $request): Response
    {
        $time = $request->get('text');
        Utils::validate($time, 'time');
        $this->postTime = $time;

        return new Response('Значение *post_time* установлено');
    }

    public function postBurndown(string $channel): bool
    {
        $imgDir = 'img/' . $channel;
        if (!is_dir($imgDir) && !mkdir($imgDir, 0777, true)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $imgDir));
        }
        $imgPath = $this->createChart($imgDir);

        $chart = new Attachment('info');
        $chart->setImageUrl($this->serverUrl . '/' . $imgPath);

        $message = new SlackMessage($channel);
        $message->setChannel($channel);
//        $message->appendAttachment($chart);
        $message->setUsername('burndown-bot');
        $this->bot->send($message);

        return $imgPath;
    }


    private function createChart($imgDir): string
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
        $reader = new SprintJsonReader($jsonData);
        $builder = new BurnDownBuilder($reader);

        return $builder->build($imgDir);
    }
}
