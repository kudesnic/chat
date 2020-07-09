<?php

namespace App\Validator;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Validator\ValidatorInterface;

/**
 * @Annotation
 */
class CustomUuidValidator implements ValidatorInterface
{
    /**
     * Regular expression pattern for matching a UUID of any variant.
     */
    private const VALID_PATTERN = '^[0-9A-Fa-f]{8}[0-9A-Fa-f]{4}[0-9A-Fa-f]{4}[0-9A-Fa-f]{4}[0-9A-Fa-f]{12}$';

    /**
     * @psalm-return non-empty-string
     * @psalm-suppress MoreSpecificReturnType we know that the retrieved `string` is never empty
     * @psalm-suppress LessSpecificReturnStatement we know that the retrieved `string` is never empty
     */
    public function getPattern(): string
    {
        return self::VALID_PATTERN;
    }

    public function validate(string $uuid): bool
    {
        $uuid = str_replace(['urn:', 'uuid:', 'URN:', 'UUID:', '{', '}'], '', $uuid);

        return $uuid === Uuid::NIL || preg_match('/' . self::VALID_PATTERN . '/D', $uuid);
    }
}
