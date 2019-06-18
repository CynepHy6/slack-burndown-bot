<?php


namespace App\Command;


use App\Controller\BotController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WowApps\SlackBundle\Service\SlackBot;

class PostBurndownCommand extends Command
{
    protected static $defaultName = 'app:post-burndown';

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var SlackBot
     */
    private $bot;

    /**
     * PostBurndownCommand constructor.
     *
     * @param SlackBot               $bot
     * @param EntityManagerInterface $em
     */
    public function __construct(SlackBot $bot, EntityManagerInterface $em)
    {
        $this->bot = $bot;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Отправка графиков сгорания в их каналы по расписанию');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bc = new BotController($this->bot, $this->em);
        $bc->sheduleRun();
    }

}
