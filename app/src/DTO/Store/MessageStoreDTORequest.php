<?php
namespace App\DTO\Store;

use App\DTO\DTORequestAbstract;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\CustomUuidValidator;
use App\Validator\IsExistingId;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class MessageStoreDTORequest extends DTORequestAbstract
{

    /**
     * @Assert\NotNull
     */
    public $text;

    /**
     * @CustomUuidValidator
     */
    public $chat_uuid;

    /**
     * @IsExistingId(entityClass = User::class)
     */
    public $to_user_id;


    public function validate(ExecutionContextInterface $context, $payload)
    {
        if(is_null($this->chat_uuid) && is_null($this->to_user_id)){
            $context->buildViolation('chat_uuid or to_user_id should be present!')
                ->atPath('chat_uuid')
                ->atPath('to_user_id')
                ->addViolation();
        }
    }

}