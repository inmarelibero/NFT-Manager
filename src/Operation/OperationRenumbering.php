<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Helper\MetadataHelper;
use Inmarelibero\NFTManager\Model\NFT;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OperationRenumbering
 * @package Inmarelibero\NFTManager\Operation
 *
 * Iterate on all NFTs and renumbers them starting from the specified id (likely #0 or #1).
 * Can also be used to fill the holes
 */
class OperationRenumbering extends OperationAbstract
{
    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                /**
                 * renumber tokens by starting from this index
                 */
                'start_from_id' => null,

                /**
                 * update the "image" metadata
                 *
                 * you can put the token ID by using the placeholder {id}, like this: 'TOKEN #{id}'
                 */
                'update_metadata_image' => null,

                /**
                 * update the "name" metadata
                 *
                 * you can put the token ID by using the placeholder {id}, like this: 'TOKEN #{id}'
                 */
                'update_metadata_name' => null,
            ])
            ->setAllowedTypes('start_from_id', ['int'])
            ->setRequired(['start_from_id'])
            ->setAllowedTypes('update_metadata_image', ['null', 'string'])
            ->setAllowedTypes('update_metadata_name', ['null', 'string'])
        ;
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws \Inmarelibero\NFTManager\Exception\AppException
     */
    public function handleImage(NFT $nft, array $options = []): void
    {
        $nft->setTokenID($this->calculateNewId($options));

        $this->writeImageIntoOutputFolder($nft->getTokenID(), $nft->getImageAbsolutePath());
    }

    /**
     * @param NFT $nft
     * @param array $options
     */
    public function handleMetadata(NFT $nft, array $options = []): void
    {
        $nft->setTokenID($this->calculateNewId($options));

        $metadata = $nft->getMetadata();

        /*
         * update metadata
         */
        if ($options['update_metadata_image'] !== null) {
            $metadata['image'] = MetadataHelper::replaceIdPlaceholder($options['update_metadata_image'], $nft);
        }
        if ($options['update_metadata_name'] !== null) {
            $metadata['name'] = MetadataHelper::replaceIdPlaceholder($options['update_metadata_name'], $nft);
        }

        /*
         * write metadata
         */
        $this->writeMetadataIntoOutputFolder($nft->getTokenID(), $metadata);
    }

    /**
     * @param array $options
     * @return int
     */
    private function calculateNewId(array $options): int
    {
        $startingID = (int) $options['start_from_id'];
        $iterationIndex = (int) $options['iteration_index'];

        return $startingID + $iterationIndex;
    }
}
