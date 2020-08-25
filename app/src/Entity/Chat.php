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
    const STRATEGY_INTERNAL_GROUP_CHAT = 'internal_group_chat';
    const STRATEGY_EXTERNAL_CHAT = 'external_chat';
    const STRATEGIES = [
        self::STRATEGY_INTERNAL_CHAT,
        self::STRATEGY_EXTERNAL_CHAT,
        self::STRATEGY_INTERNAL_GROUP_CHAT
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
     * @ORM\OneToMany(targetEntity=Participant::class, mappedBy="chat")
     */
    private $participants;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->participants = new ArrayCollection();

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $user): self
    {
        $this->owner = $user;

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

    /**
     * @return Collection|Participant[]
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
            $participant->setChat($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->contains($participant)) {
            $this->participants->removeElement($participant);
            // set the owning side to null (unless already changed)
            if ($participant->getChat() === $this) {
                $participant->setChat(null);
            }
        }

        return $this;
    }

}
