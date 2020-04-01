<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

class Base64ImageService
{
    /**
     * Converts base64 image data to file
     * @param string $value
     *
     * @return File
     */
    public function convertToFile(string $value): File
    {
        if (strpos($value, ';base64') !== false) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData = base64_decode($value);
        $tmpFile = tempnam(sys_get_temp_dir(), 'base64validator');
        file_put_contents($tmpFile, $binaryData);

        return new File($tmpFile);
    }

}