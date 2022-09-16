<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Helper\FileSystemHelper;
use Inmarelibero\NFTManager\Model\NFT;
use Inmarelibero\NFTManager\NFTManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OperationAbstract
 * @package Inmarelibero\NFTManager\Operation
 */
class OperationAbstract implements OperationInterface
{
    /**
     * @var NFTManager
     */
    protected $nftHandler;

    /**
     * OperationInterface constructor.
     * @param NFTManager $nftHandler
     */
    public function __construct(NFTManager $nftHandler)
    {
        $this->nftHandler = $nftHandler;
    }

    /**
     * @param NFT[] $nfts
     * @param array $options
     * @return NFT[]
     * @throws AppException
     */
    public function preExecute(array $nfts, array $options = []): array
    {
        return $nfts;
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    final public function execute(NFT $nft, array $options = []): void
    {
        $this->handleImage($nft, $options);
        $this->handleMetadata($nft, $options);
    }

    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver());
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    protected function handleImage(NFT $nft, array $options = []): void
    {
        $this->nftHandler->cloneSourceImage($nft);
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    protected function handleMetadata(NFT $nft, array $options = []): void
    {
        $this->nftHandler->cloneSourceMetadata($nft);
    }

    /**
     * As default action when skipping execution, copy original image and metadata
     *
     * @param NFT $nft
     * @throws AppException
     */
    public function onSkip(NFT $nft): void
    {
        $this->nftHandler->cloneSourceImage($nft);
        $this->nftHandler->cloneSourceMetadata($nft);
    }

    /**
     * @param int $tokenId
     * @param array $metadata
     */
    protected function writeMetadataIntoOutputFolder(int $tokenId, array $metadata): void
    {
        // write metadata file
        $newMetadataAbsolutePath = sprintf('%s/%s', $this->nftHandler->getMetadataFinalOutputFolder(), $tokenId);

        FileSystemHelper::writeJSONIntoFile($newMetadataAbsolutePath, $metadata);
    }

    /**
     * @param int $tokenId
     * @param string $inputImageAbsolutePath
     * @throws AppException
     */
    protected function writeImageIntoOutputFolder(int $tokenId, string $inputImageAbsolutePath): void
    {
        $this->nftHandler->writeImage($tokenId, $inputImageAbsolutePath);
    }
}
