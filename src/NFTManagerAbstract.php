<?php

namespace Inmarelibero\NFTManager;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Exception\FilesystemException;
use Inmarelibero\NFTManager\Helper\FileSystemHelper;
use Inmarelibero\NFTManager\Model\NFT;
use Inmarelibero\NFTManager\Operation\OperationInterface;

/**
 * Class NFTManagerAbstract
 * @package Inmarelibero\NFTManager
 */
abstract class NFTManagerAbstract
{
    /**
     * WITHOUT trailing "/"
     *
     * @var string
     */
    private $projectRoot;

    /*
     * how many operations have been finalized
     */
    private $executedOperations = 0;


    /**
     * @return string
     */
    protected function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * @param string $projectRoot
     * @throws AppException
     */
    protected function setProjectRoot(string $projectRoot): void
    {
        if (!file_exists($projectRoot)) {
            throw new AppException(sprintf('Unable to set directory "%s" as project root: directory does not exist.', $projectRoot));
        }

        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * @return bool
     */
    protected function isBeforeFirstOperation(): bool
    {
        return $this->executedOperations <= 0;
    }

    /**
     * @return int
     */
    protected function getIndexOfExecutingOperation(): int
    {
        return $this->executedOperations;
    }

    /**
     * @throws AppException
     */
    protected function initOutputFolders(): void
    {
        FileSystemHelper::deleteAndCreateDirectory($this->getFinalOutputFolder());
        FileSystemHelper::createDirectory($this->getImagesFinalOutputFolder());
        FileSystemHelper::createDirectory($this->getMetadataFinalOutputFolder());
    }

    /**
     * @return string
     */
    protected function getFinalOutputFolder(): string
    {
        return $this->getProjectRoot().'/output';
    }

    /**
     * @return string
     */
    public function getImagesFinalOutputFolder(): string
    {
        return $this->getFinalOutputFolder().'/images';
    }

    /**
     * @return string
     */
    public function getMetadataFinalOutputFolder(): string
    {
        return $this->getFinalOutputFolder().'/metadata';
    }

    /**
     * Return the absolute path of the folder to user as NFT and metadata input
     *
     * Before every operation is executed, the "output" folder is copied into a temporary folder (eg. ".input-tmp")
     * and this temporary folder is used sa input
     *
     * @return string
     */
    protected function getOperationInputFolder(): string
    {
        if ($this->isBeforeFirstOperation()) {
            return $this->getProjectRoot().'/input';
        }

        return $this->getProjectRoot().'/.input-tmp';
    }

    /**
     * @return string
     */
    protected function getImagesOperationInputFolder(): string
    {
        return $this->getOperationInputFolder().'/images';
    }

    /**
     * @return string
     */
    protected function getMetadataOperationInputFolder(): string
    {
        return $this->getOperationInputFolder().'/metadata';
    }

    /**
     * @return NFT[]
     * @throws AppException
     */
    protected function generateNFTs(): array
    {
        $imagesPathsArray = FileSystemHelper::getFilesInDir($this->getImagesOperationInputFolder());
        $metadataPathsArray = FileSystemHelper::getFilesInDir($this->getMetadataOperationInputFolder());

        $output = [];

        foreach ($imagesPathsArray as $k => $imageFullPath) {
            $metadata = json_decode(file_get_contents($metadataPathsArray[$k]), true);

            $output[] = new NFT($imageFullPath, $metadata);
        }

        return $output;
    }

    /**
     * @throws AppException
     */
    private function copyPreviousOperationOutputFolderContentIntoTemporaryInputFolder(): void
    {
        if ($this->isBeforeFirstOperation()) {
            throw new AppException("No previous operations have been executed: there is no previous output to copy");
        }

        FileSystemHelper::deleteAndCreateDirectory($this->getProjectRoot().'/.input-tmp');
        FileSystemHelper::deepCopy($this->getFinalOutputFolder(), $this->getProjectRoot().'/.input-tmp/');
    }

    /**
     * @throws AppException
     */
    protected function preExecuteOperation(): void
    {
        if (!$this->isBeforeFirstOperation()) {
            $this->copyPreviousOperationOutputFolderContentIntoTemporaryInputFolder();
        }
    }

    /**
     * @param OperationInterface $operation
     * @throws AppException
     */
    protected function postExecuteOperation(OperationInterface $operation): void
    {
        FileSystemHelper::deleteDirectory($this->getProjectRoot().'/.input-tmp');

        /**
         * clean up
         */
        try {
            FileSystemHelper::deleteFile($this->getImagesFinalOutputFolder() . "/.DS_Store");
            FileSystemHelper::deleteFile($this->getMetadataFinalOutputFolder() . "/.DS_Store");
        } catch (FilesystemException $exception) {
        }

        $this->executedOperations++;
    }

    /**
     * Copy the original image from input
     *
     * @param NFT $nft
     * @throws AppException
     */
    public function cloneSourceImage(NFT $nft): void
    {
        $src = $nft->getImageAbsolutePath();

        $this->writeImage($nft->getTokenID(), $src);
    }

    /**
     * Copy the original metadata from input
     *
     * @param NFT $nft
     * @throws AppException
     */
    public function cloneSourceMetadata(NFT $nft): void
    {
        $src = $this->getMetadataOperationInputFolder().'/'.$nft->getTokenID();
        $dest = sprintf('%s/%s', $this->getMetadataFinalOutputFolder(), $nft->getTokenID());

        // copy image
        if (!copy($src, $dest)) {
            throw new AppException(sprintf('Error while copying "%s" into "%s".', $src, $dest));
        }
    }

    /**
     * @param int $tokenId
     * @param string $source
     * @throws AppException
     */
    public function writeImage(int $tokenId, string $source): void
    {
        $destFilename = sprintf('%s.png', $tokenId);
        $dest = sprintf('%s/%s', $this->getImagesFinalOutputFolder(), $destFilename);

        if (!copy($source, $dest)) {
            throw new AppException(sprintf('Error while copying image "%s" into "%s".', $source, $destFilename));
        }
    }
}
