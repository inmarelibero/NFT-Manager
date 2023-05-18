<?php

namespace Inmarelibero\NFTManager\Helper;

use Inmarelibero\NFTManager\Model\NFT;

/**
 * Class MetadataHelper
 * @package Inmarelibero\NFTManager\Helper
 */
class MetadataHelper
{
    /**
     * @param array $metadata
     * @param string $traitType
     * @param string $value
     * @return array
     */
    public static function updateTrait(array $metadata, string $traitType, string $value): array
    {
        $attributes = $metadata['attributes'];

        $traitTypeExists = count(array_filter($attributes, function ($traitTypeAndValue) use ($traitType) {
            return $traitTypeAndValue['trait_type'] === $traitType;
        })) > 0;

        if ($traitTypeExists) {
            foreach ($attributes as $k => $attribute) {
                if ($attribute['trait_type'] === $traitType) {
                    $attributes[$k] = [
                        "trait_type" => $traitType,
                        "value" => $value,
                    ];
                }
            }
        } else {
            $attributes[] = [
                "trait_type" => $traitType,
                "value" => $value,
            ];
        }

        $metadata['attributes'] = $attributes;

        return $metadata;
    }

    /**
     * @param array $metadata
     * @param string $traitType
     * @return array
     */
    public static function removeTrait(array $metadata, string $traitType): array
    {
        $attributes = $metadata['attributes'];

        $traitTypeExists = count(array_filter($attributes, function ($traitTypeAndValue) use ($traitType) {
            return $traitTypeAndValue['trait_type'] === $traitType;
        })) > 0;

        if ($traitTypeExists) {
            foreach ($attributes as $k => $attribute) {
                if ($attribute['trait_type'] === $traitType) {
                    unset($attributes[$k]);
                }
            }
        }

        $metadata['attributes'] = $attributes;

        return $metadata;
    }

    /**
     * @param string $input
     * @param NFT $nft
     * @param string $placeholder
     * @return string
     */
    public static function replaceIdPlaceholder(string $input, NFT $nft, string $placeholder = '{id}'): string
    {
        return str_replace($placeholder, $nft->getTokenID(), $input);
    }
}
