<?php


namespace App\Controller;


use App\BurnDownBuilder;
use App\Entity\Channel;
use App\SprintJsonReader;
use App\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WowApps\SlackBundle\DTO\Attachment;
use WowApps\SlackBundle\DTO\SlackMessage;
use WowApps\SlackBundle\Service\SlackBot;

class BotController extends AbstractController
{
    private $bot;
    private $em;
    public const SERVER_URL    = 'https://simpletask.skyeng.tech/slack-burndown-bot/';
    public const RAPID_VIEW_ID = 'rapid_view_id';
    public const POST_TIME     = 'post_time';
    public const SPRINT_ID     = 'sprint_id';


    /**
     * BotController constructor.
     *
     * @param SlackBot               $bot
     * @param EntityManagerInterface $em
     */
    public function __construct(SlackBot $bot, EntityManagerInterface $em)
    {
        $this->bot = $bot;
        $this->em = $em;

    }

    /**
     * Выполнение отправки графика в канал по расписанию
     *
     * @param OutputInterface $output
     */
    public function sheduleRun(OutputInterface $output): void
    {
        $time = date('H:i');

        /** @var Channel[] $channels */

        $channels = $this->em->getRepository(Channel::class)->findBy(['post_time' => $time]);
        foreach ($channels as $channel) {
            $this->postBurndown($channel->getChannelId());
        }
    }

    /**
     * @Route("/")
     * @return Response
     */
    public function index(): Response
    {
        //        $res = $this->createChart('.', 'GK19P65UG'); //906
        //        $res = $this->createChart('.', 'GK9T8DU7N'); //916
        return new Response(
            "<html lang='en'><body>IT'S WORKS</body></html>"
        //            "<html lang='en'><body bgcolor='black'>IT'S WORKS<p><img src='$res' alt=''></p></body></html>"
        );
    }

    public function paramStore(string $channelId, $param, string $type): Response
    {
        if (!Utils::validate($param, $type)) {
            return new Response("Неправильный формат данных. Значение *$type* не установлено");
        }
        try {
            if (!$channel = $this->em->getRepository(Channel::class)
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
            $this->em->persist($channel);
            $this->em->flush();
        } catch (Exception $e) {
            return new Response($e->getMessage());
        }
        return new Response("Значение *$type* установлено");
    }

    /**
     * @Route("/show")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $channelId = $request->get('channel_id');
        return new JsonResponse($this->postBurndown($channelId, true));
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

    /**
     * @param string $channelId
     *
     * @param bool   $isResponse
     *
     * @return array|string
     */
    public function postBurndown(string $channelId, $isResponse = false)
    {
        if (!$channel = $this->em->getRepository(Channel::class)
            ->findOneBy(['channel_id' => $channelId])) {
            return 'Channel not found';
        }
        if (!$webhook = $channel->getWebhook()) {
            return 'Webhook not found';
        }
        $imgDir = 'img/' . $channelId;

        $imgPath = $this->generateChart($imgDir, $channelId);
        $imgUrl = self::SERVER_URL . $imgPath;
        if ($isResponse) { // команда /show
            $pretext = [
                'post_time'     => $channel->getPostTime(),
                'rapid_view_id' => $channel->getRapidViewId(),
                'sprint_id'     => $channel->getSprintId(),
                'channel_id'    => $channel->getChannelId(),
            ];
            $attach = [
                'attachments' => [
                    [
                        'pretext'   => json_encode($pretext, JSON_PRETTY_PRINT),
                        'color'     => '#2196F3',
                        'image_url' => $imgUrl,
                    ],
                ],
            ];
            return $attach;
        }

        $chart = new Attachment('info');
        $chart->setImageUrl($imgUrl);

        $message = new SlackMessage();
        $message->setChannel($channel->getName());
        $message->appendAttachment($chart);
        $message->setUsername('burndown-bot');

        $config = $this->bot->getConfig();
        $config['api_url'] = $webhook;
        $this->bot->setConfig($config);
        $this->bot->send($message);
        return $imgUrl;
    }


    private function generateChart(string $imgDir, string $channelId): string
    {
        if (is_dir('public')) {
            //            костыл  для одинакового поведения при запуске из консоли и с реквеста
            chdir('public');
        }
        if (!is_dir($imgDir) && !mkdir($imgDir, 0777, true)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $imgDir));
        }
        if (!$channel = $this->em->getRepository(Channel::class)
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
        $key = $_ENV['ATLASSIAN_API_TOKEN'];

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
