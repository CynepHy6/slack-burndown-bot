<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     */
    public function setRapidViewId(int $viewId): void
    {
        $this->rapidViewId = $viewId;
    }

    /**
     * @Route("/set_sprint/{sprintId}")*
     * @param int $sprintId
     */
    public function setSprintId($sprintId): void
    {
        $this->sprintId = $sprintId;
    }

    /**
     * @Route("/set_post_time/{postTime}")
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
