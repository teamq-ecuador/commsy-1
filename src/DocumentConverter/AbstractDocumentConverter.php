<?php


namespace App\DocumentConverter;


abstract class AbstractDocumentConverter implements DocumentConverterInterface
{
    protected $formatsAllowed = [];
    protected $fileName = '';

    public function supportsFormat(string $fileExtension) : bool {
        return in_array(str_replace('.', '', $fileExtension), $this->formatsAllowed);
    }
}