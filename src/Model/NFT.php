<?php

namespace Inmarelibero\NFTManager\Model;

use Inmarelibero\NFTManager\Exception\AppException;

/**
 * Class NFT
 * @package Inmarelibero\NFTManager\Model
 */
class NFT
{
    /** @var string */
    private $imageAbsolutePath;

    /** @var array */
    private $metadata;

    /** @var string */
    private $tokenID;

    /** @var string */
    private $fileName;

    /** @var string */
    private $fileExtension;

    /**
     * NFT constructor.
     * @param string $imageAbsolutePath
     * @param array $metadata
     * @throws AppException
     */
    public function __construct(string $imageAbsolutePath, array $metadata)
    {
        $this->setImageAbsolutePath($imageAbsolutePath);
        $this->setMetadata($metadata);

        $absolutePathTokens = explode('/', $imageAbsolutePath);
        $filename = $absolutePathTokens[count($absolutePathTokens)-1];

        //
        if (substr_count($filename, '.') != 1) {
            throw new AppException(sprintf('Filename "%s" not valid: it does not contain exactly 1 ".".', $filename));
        }

        $this->setFileName($filename);

        $filenameTokens = explode('.', $filename);

        if (!isset($filenameTokens[0])) {
            throw new AppException(sprintf('Unable to get token ID from file "%s".', $filename));
        }

        if (!isset($filenameTokens[1])) {
            throw new AppException(sprintf('Unable to get extension from file "%s".', $filename));
        }

        $this->setTokenID($filenameTokens[0]);
        $this->setFileExtension($filenameTokens[1]);
    }

    /**
     * @return string
     */
    public function getImageAbsolutePath(): string
    {
        return $this->imageAbsolutePath;
    }

    /**
     * @param string $imageAbsolutePath
     */
    private function setImageAbsolutePath(string $imageAbsolutePath): void
    {
        $imageAbsolutePath = realpath($imageAbsolutePath);

        if (!$imageAbsolutePath) {
            throw new AppException("Unable to generate real path for path \"$imageAbsolutePath\"");
        }

        if (!file_exists($imageAbsolutePath)) {
            throw new AppException("File \"$imageAbsolutePath\" does not exist.");
        }

        $this->imageAbsolutePath = $imageAbsolutePath;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     */
    private function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return int
     */
    public function getTokenID(): int
    {
        return (int) $this->tokenID;
    }

    /**
     * @param string $tokenID
     */
    public function setTokenID(string $tokenID): void
    {
        $this->tokenID = (int) $tokenID;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    private function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * @param string $fileExtension
     */
    private function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }
}
