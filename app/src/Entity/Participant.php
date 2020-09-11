<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ParticipantRepository::class)
 */
class Participant
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $chat_id;

    /**
     * @ORM\ManyToOne(targetEntity=Chat::class, inversedBy="participants")
     * @ORM\JoinColumn(name="chat_id", nullable=false, onDelete="CASCADE")
     */
    private $chat;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="participants")
     */
    private $client;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $unread_messages_count;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="participants")
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChatId(): int
    {
        return $this->chat_id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getUnreadMessagesCount(): ?int
    {
        return $this->unread_messages_count;
    }

    public function setUnreadMessagesCount(?int $unread_messages_count): self
    {
        $this->unread_messages_count = $unread_messages_count;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
