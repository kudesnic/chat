<?php
namespace App\DTO\Store;

use App\DTO\DTORequestAbstract;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\CustomUuidValidator;


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

}