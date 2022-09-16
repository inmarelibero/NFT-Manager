<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Helper\MetadataHelper;
use Inmarelibero\NFTManager\Model\NFT;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OperationShuffle
 * @package Inmarelibero\NFTManager\Operation
 */
class OperationShuffle extends OperationAbstract
{
    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                /**
                 * update the "image" metadata value
                 * this is necessary because when shuffling a new id is assigned to the NFT, and most likely the image will change
                 *
                 * eg. old image = "https://example.com/125.png"
                 *     new image = "https://example.com/852.png"
                 *
                 * placeholders:
                 *  - "{id}": if you specify a string containing "{id}", it will be replaced by the NFT id
                 *           eg. if "update_metadata_image" = "Token #{id}", then the "image" metadata will be "Token #127"
                 */
                'update_metadata_image' => null,

                /**
                 * update the "name" metadata value
                 * this is necessary because when shuffling a new id is assigned to the NFT, and most likely the name will change
                 *
                 * eg. old name = "NFT #125"
                 *     new name = "NFT #852"
                 *
                 * placeholders:
                 *  - "{id}": if you specify a string containing "{id}", it will be replaced by the NFT id
                 *           eg. if "update_metadata_name" = "Token #{id}", then the "name" metadata will be "Token #127"
                 */
                'update_metadata_name' => null,
            ])
            ->setAllowedTypes('update_metadata_image', ['string'])
            ->setAllowedTypes('update_metadata_name', ['string'])
        ;
    }

    /**
     * @param NFT[] $nfts
     * @param array $options
     * @return NFT[]
     * @throws AppException
     */
    public function preExecute(array $nfts, array $options = []): array
    {
        shuffle($nfts);

        return $nfts;
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    public function handleImage(NFT $nft, array $options = []): void
    {
        $iterationIndex = $options['iteration_index'];

        $newId = $iterationIndex;
        $nft->setTokenID($newId);

        $this->writeImageIntoOutputFolder($nft->getTokenID(), $nft->getImageAbsolutePath());
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    public function handleMetadata(NFT $nft, array $options = []): void
    {
        $iterationIndex = $options['iteration_index'];

        $newId = $iterationIndex;
        $nft->setTokenID($newId);

        /*
         * handle metadata
         */
        $this->writeMetadataIntoOutputFolder(
            $nft->getTokenID(),
            $this->updateMetadata($nft, $options)
        );
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @return array
     */
    private function updateMetadata(NFT $nft, array $options = []): array
    {
        $metadata = $nft->getMetadata();

        /**
         * update "name"
         */
        $metadata['name'] = MetadataHelper::replaceIdPlaceholder($options['update_metadata_name'], $nft);

        /**
         * update "image"
         */
        $metadata['image'] = MetadataHelper::replaceIdPlaceholder($options['update_metadata_image'], $nft);

        /**
         * return
         */
        return $metadata;
    }
}
