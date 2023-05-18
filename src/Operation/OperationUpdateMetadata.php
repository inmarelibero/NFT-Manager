<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Model\NFT;
use Inmarelibero\NFTManager\Helper\MetadataHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OperationUpdateMetadata
 * @package Inmarelibero\NFTManager\Operation
 */
class OperationUpdateMetadata extends OperationAbstract
{
    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                /**
                 * array like: [
                 *      'trait to update' => 'new value',
                 *      'trait 2 to update' => 'new value',
                 *      'trait 3 to update' => 'new value',
                 *      ...
                 * ]
                 */
                'update_attributes' => [],

                /**
                 * update the base URI, by building a final URL like:
                 *  $options['update_base_uri'].'/'. $tokenId
                 */
                'update_base_uri' => null,

                /**
                 * update the "external_url" metadata
                 *
                 * you can put the token ID by using the placeholder {id}, like this: 'TOKEN #{id}'
                 */
                'update_external_url' => null,

                /**
                 * update the "name" metadata
                 *
                 * you can put the token ID by using the placeholder {id}, like this: 'TOKEN #{id}'
                 */
                'update_name' => null,

                /**
                 * array of metadata to remove, eg. ["Hat", "Skull", ...]
                 */
                'remove_attributes' => [],

                /**
                 * array of metadata to remove, eg. ["dna", "description", ...]
                 */
                'remove_values' => null,
            ])
            ->setAllowedTypes('update_attributes', ['array'])
            ->setAllowedTypes('update_base_uri', ['null', 'string'])
            ->setAllowedTypes('update_external_url', ['null', 'string'])
            ->setAllowedTypes('update_name', ['null', 'string'])
            ->setAllowedTypes('remove_attributes', ['null', 'string[]'])
            ->setAllowedTypes('remove_values', ['null', 'string[]'])
        ;
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    public function handleMetadata(NFT $nft, array $options = []): void
    {
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

        // update traits
        foreach ($options['update_attributes'] as $trait => $value) {
            $metadata = MetadataHelper::updateTrait($metadata, $trait, $value);
        }

        // update base URI
        if ($options['update_base_uri'] !== null) {
            $baseImagesUri = rtrim($options['update_base_uri'], '/');

            $metadata['image'] = sprintf('%s/%s', $baseImagesUri, $nft->getFileName());
        }

        // update external_url
        if ($options['update_external_url'] !== null) {
            $metadata['external_url'] = MetadataHelper::replaceIdPlaceholder($options['update_external_url'], $nft);
        }

        // update name
        if ($options['update_name'] !== null) {
            $metadata['name'] = MetadataHelper::replaceIdPlaceholder($options['update_name'], $nft);
        }

        // remove traits
        if ($options['remove_attributes'] !== null) {
            foreach ($options['remove_attributes'] as $trait) {
                $metadata = MetadataHelper::removeTrait($metadata, $trait);
            }
        }

        // remove metadata values
        if ($options['remove_values'] !== null) {
            foreach ($options['remove_values'] as $keyToRemove) {
                unset($metadata[$keyToRemove]);
            }
        }

        return $metadata;
    }
}
