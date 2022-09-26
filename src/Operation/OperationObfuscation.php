<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Model\NFT;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OperationObfuscation
 * @package Inmarelibero\NFTManager\Operation
 */
class OperationObfuscation extends OperationAbstract
{
    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                /**
                 * specify the absolute path of the image to use in place of the original one
                 */
                'placeholder_image_absolute_path' => null,

                /**
                 * specify the string to use instead of the real metadata values
                 */
                'placeholder_value' => '?',
            ])
            ->setAllowedTypes('placeholder_image_absolute_path', ['string', 'null'])
            ->setRequired('placeholder_image_absolute_path')
            ->setAllowedTypes('placeholder_value', ['string'])
            ->setRequired('placeholder_value')
        ;
    }

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    public function handleImage(NFT $nft, array $options = []): void
    {
        $sourceImagePath = $nft->getImageAbsolutePath();

        if ($options['placeholder_image_absolute_path'] !== null) {
            $sourceImagePath = $options['placeholder_image_absolute_path'];
        }

        // write image
        $this->writeImageIntoOutputFolder($nft->getTokenID(), $sourceImagePath);
    }

    /**
     * @param NFT $nft
     * @param array $options
     */
    public function handleMetadata(NFT $nft, array $options = []): void
    {
        $metadata = $this->obfuscateTraits($nft, $options['placeholder_value']);

        // write metadata file
        $this->writeMetadataIntoOutputFolder($nft->getTokenID(), $metadata);
    }

    /**
     * @param NFT $nft
     * @param string $placeholder
     * @return array
     */
    private function obfuscateTraits(NFT $nft, string $placeholder = '?'): array
    {
        $metadata = $nft->getMetadata();
        $attributes = $metadata['attributes'];

        foreach ($attributes as $k => $attribute) {
            $attributes[$k]['value'] = $placeholder;
        }

        $metadata['attributes'] = $attributes;

        return $metadata;
    }
}
