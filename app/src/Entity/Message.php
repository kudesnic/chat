<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="message_seq", initialValue=1)
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $chat_id;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $parent_id;

    /**
     * @ORM\Column(type="string", length=32767, nullable=true)
     */
    private $text;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $client_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file_path;

    /**
     * @ORM\Column(type="integer")
     */
    private $user_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Chat", inversedBy="message")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $chat;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Message", inversedBy="messages")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="parent")
     */
    private $messages;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="messages")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChatId(): int
    {
        return $this->chat_id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }


    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(?string $file_path): self
    {
        $this->file_path = $file_path;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getOrder(): ?self
    {
        return $this->parent;
    }

    public function setOrder(?self $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(self $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setParent($this);
        }

        return $this;
    }

    public function removeMessage(self $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getParent() === $this) {
                $message->setParent(null);
            }
        }

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