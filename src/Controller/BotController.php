<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    /**
     * @Route("/")
     * @return Response
     */
    public function index(): Response
    {
        return new Response(
            '<html><body>SERVER WORKS</body></html>'
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
    }

    /**
     * @Route("/set_rapid_view/{viewId}")
     * @param int $viewId
     *
     * @return JsonResponse
     */
    public function setRapidViewId(int $viewId): JsonResponse
    {
        $this->rapidViewId = $viewId;
        return $this->json(true);
    }

    /**
     * @Route("/set_sprint/{sprintId}")
     * @param int $sprintId
     *
     * @return JsonResponse
     */
    public function setSprintId($sprintId): JsonResponse
    {
        $this->sprintId = $sprintId;
        return $this->json(true);
    }

    /**
     * @Route("/set_post_time/{postTime}")
     * @param string $postTime
     *
     * @return JsonResponse
     */
    public function setPostTime($postTime): JsonResponse
    {
        $this->postTime = $postTime;
        return $this->json(true);
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
