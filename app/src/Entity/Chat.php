<?php

namespace App\Entity;

use App\Validator\CustomUuidValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


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
    private $owner_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $strategy;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="chat", fetch="EXTRA_LAZY")
     */
    private $messages;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="chats")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="own_chats")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * Property increments automatically. Check MessageEntityListener for details.
     * This field added to chat entity for better performance
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $unread_messages_count;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * This field added to chat entity for better performance
     *
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="last_active_chats")
     */
    private $last_active_user;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
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

    public function getOwnerId(): ?int
    {
        return $this->owner_id;
    }

    public function setOwnerId(int $owner_id): self
    {
        $this->owner_id = $owner_id;

        return $this;
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
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setChat($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getChat() === $this) {
                $message->setChat(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $user): self
    {
        $this->owner = $user;

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

    public function getUnreadMessagesCount(): ?int
    {
        return $this->unread_messages_count;
    }

    public function setUnreadMessagesCount(?int $unread_messages_count): self
    {
        $this->unread_messages_count = $unread_messages_count;

        return $this;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getLastActiveUser(): ?User
    {
        return $this->last_active_user;
    }

    public function setLastActiveUser(?User $last_active_user): self
    {
        $this->last_active_user = $last_active_user;

        return $this;
    }
}
