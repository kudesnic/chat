<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 *  Processes base64 string, converts to file and saves
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class Base64ImageService
{
    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * Base64ImageService constructor.
     * @param ParameterBagInterface $parameters
     */
    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Converts base64 image data to file
     * @param string $value
     *
     * @return File
     */
    public function convertToFile(string $value): File
    {
        if (strpos($value, ';base64') !== false) {
            //puts explode(';', $value)[1] into $value
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData = base64_decode($value);
        $tmpFile = tempnam(sys_get_temp_dir(), 'base64validator');
        file_put_contents($tmpFile, $binaryData);

        return new File($tmpFile);
    }

    /**
     * Saves base64 image
     *
     * @param string $imgEncoded
     * @param string $fileDirectory
     * @param string $fileName
     * @param string|null $fileTodelete
     * @return string
     */
    public function saveImage(
        string $imgEncoded,
        string $fileDirectory,
        string $fileName,
        ?string $fileTodelete = null
    ):string {

        if(is_null($fileTodelete) == false && file_exists($fileTodelete)){
            unlink($fileTodelete);
        }

        $imgFile = $this->convertToFile($imgEncoded);
        $imgName = $fileName . '.' . $imgFile->guessExtension();
        $fileDirectory =  $imgDirectory = $this->parameters->get('upload_path') . '/'. $fileDirectory;
        $imgFile = $imgFile->move(
            $fileDirectory,
            $imgName
        );

        return $imgFile->getPathname();
    }

}