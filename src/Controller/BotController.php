<?php


namespace App\Controller;


use App\BurnDownBuilder;
use App\Entity\Channel;
use App\SprintJsonReader;
use App\Utils;
use Exception;
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
    private $token;
    private $serverUrl;
    public const RAPID_VIEW_ID = 'rapid_view_id';
    public const POST_TIME     = 'post_time';
    public const SPRINT_ID     = 'sprint_id';


    /**
     * BotController constructor.
     *
     * @param SlackBot $bot
     */
    public function __construct(SlackBot $bot)
    {
        $this->bot = $bot;
        //        rapidViewId = '303';
        //        sprintId = '906';
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
            '<html lang="en"><body>IT\'S WORKS</body></html>'
        );
    }

    public function paramStore(string $channelId, $param, string $type): Response
    {
        if (!Utils::validate($param, $type)) {
            return new Response("Неправильный формат данных. Значение *$type* не установлено");
        }
        try {
            $em = $this->getDoctrine()->getManager();
            if (!$channel = $this->getDoctrine()
                ->getRepository(Channel::class)
                ->findOneBy(['channel_id' => $channelId])) {
                $channel = new Channel();
                $channel->setChannelId($channelId)
                    ->setRapidViewId(0)
                    ->setSprintId(0)
                    ->setPostTime('10:00')
                    ->setName($channelId);
            }

            switch ($type) {
                case static::RAPID_VIEW_ID:
                    $channel->setRapidViewId((int) $param);
                    break;
                case static::POST_TIME:
                    $channel->setPostTime((string) $param);
                    break;
                case static::SPRINT_ID:
                    $channel->setSprintId((int) $param);
                    break;
            }
            $em->persist($channel);
            $em->flush();
        } catch (Exception $e) {
            return new Response($e->getMessage());
        }
        return new Response("Значение *$type* установлено");
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
        $channelId = $request->get('channel_id');
        return $this->paramStore($channelId, $text, static::RAPID_VIEW_ID);
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
        $channelId = $request->get('channel_id');
        return $this->paramStore($channelId, $text, static::SPRINT_ID);
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
        $text = $request->get('text');
        $channelId = $request->get('channel_id');
        return $this->paramStore($channelId, $text, static::POST_TIME);
    }

    public function postBurndown(string $channelId): bool
    {
        $imgDir = 'img/' . $channelId;
        if (!is_dir($imgDir) && !mkdir($imgDir, 0777, true)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $imgDir));
        }
        $imgPath = $this->createChart($imgDir, $channelId);

        $chart = new Attachment('info');
        $chart->setImageUrl($this->serverUrl . '/' . $imgPath);

        $message = new SlackMessage($channelId);
        $message->setChannel($channelId);
        //        $message->appendAttachment($chart);
        $message->setUsername('burndown-bot');
        $this->bot->send($message);

        return $imgPath;
    }


    private function createChart(string $imgDir, string $channelId): string
    {
        if (!$channel = $this->getDoctrine()
            ->getRepository(Channel::class)
            ->findOneBy(['channel_id' => $channelId])) {
            return '';
        }
        $rapidViewId = $channel->getRapidViewId();
        $sprintId = $channel->getSprintId();
        $params = http_build_query([
            'rapidViewId' => $rapidViewId,
            'sprintId'    => $sprintId,
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
