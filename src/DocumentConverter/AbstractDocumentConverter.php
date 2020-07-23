<?php


namespace App\DocumentConverter;


abstract class AbstractDocumentConverter implements DocumentConverterInterface
{
    protected $formatsAllowed = [];

    public function supportsFormat(string $fileExtension) : bool {
        if(!is_array($this->formatsAllowed)){
            throw new \Exception("The property formatsAllowed must be an array");
        }
        return in_array(str_replace('.', '', $fileExtension), $this->formatsAllowed);
    }

    public function cleanString($string){
        return str_replace(["\r\n", "\r", "\n", "\t", "\""], '', $string);
    }
}