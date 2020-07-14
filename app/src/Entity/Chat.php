<?php

namespace App\Entity;

use App\Validator\CustomUuidValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ChatRepository")
 */
class Chat
{
    const STRATEGY_INTERNAL_CHAT = 'internal_chat';
    const STRATEGY_EXTERNAL_CHAT = 'external_chat';
    const STRATEGIES = [
        self::STRATEGY_INTERNAL_CHAT,
        self::STRATEGY_EXTERNAL_CHAT
    ];


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="chat_seq", initialValue=1)
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * The internal primary identity key.
     *
     * @ORM\Column(type="uuid")
     */
    protected $uuid;
    /**
     * @ORM\Column(type="integer")
     */
    private $user_id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $strategy;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="chat")
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="chats")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;


    public function __construct()
    {
        $this->message = new ArrayCollection();
        //wamp protocol cant use strings with dashes as a topic name, but postgres uuid column type accepts uuids without dashes
        $factory = new UuidFactory();
        $factory->setValidator(new CustomUuidValidator());

        Uuid::setFactory($factory);
        $uuid4 = Uuid::uuid4();
        echo '--------------------uuid = ' . $uuid4;
        $this->uuid = str_replace('-', '', $uuid4);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * you can send uuid without dashes to postgres, but it will save it with dashes. It is ok and such cases described in postgres docs
     * @link https://www.postgresql.org/docs/9.1/datatype-uuid.html
     * @return string
     */
    public function getUuid(): string
    {
        return  str_replace('-', '', $this->uuid);
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getStrategy(): ?string
    {
        return $this->strategy;
    }

    public function setStrategy(string $strategy): self
    {
        if(!in_array($strategy, self::STRATEGIES)){
            throw new \Exception('Chat:strategy has to be one of the next values:' . implode(', ', self::STRATEGIES));
        }
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessage(): Collection
    {
        return $this->message;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->message->contains($message)) {
            $this->message[] = $message;
            $message->setChat($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->message->contains($message)) {
            $this->message->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getChat() === $this) {
                $message->setChat(null);
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
