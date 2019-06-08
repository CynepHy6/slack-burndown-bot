<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChannelRepository")
 */
class Channel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $webhook;

    /**
     * @ORM\Column(type="integer")
     */
    private $rapid_view_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $sprint_id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $post_time;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $channel_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getWebhook(): ?string
    {
        return $this->webhook;
    }

    public function setWebhook(?string $webhook): self
    {
        $this->webhook = $webhook;

        return $this;
    }

    public function getRapidViewId(): ?int
    {
        return $this->rapid_view_id;
    }

    public function setRapidViewId(int $rapid_view_id): self
    {
        $this->rapid_view_id = $rapid_view_id;

        return $this;
    }

    public function getSprintId(): ?int
    {
        return $this->sprint_id;
    }

    public function setSprintId(int $sprint_id): self
    {
        $this->sprint_id = $sprint_id;

        return $this;
    }

    public function getPostTime(): ?string
    {
        return $this->post_time;
    }

    public function setPostTime(string $post_time): self
    {
        $this->post_time = $post_time;

        return $this;
    }

    public function getChannelId(): ?string
    {
        return $this->channel_id;
    }

    public function setChannelId(?string $channel_id): self
    {
        $this->channel_id = $channel_id;

        return $this;
    }
}
