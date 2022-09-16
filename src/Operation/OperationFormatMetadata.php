<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Model\NFT;

/**
 * Class OperationFormatMetadata
 * @package Inmarelibero\NFTManager\Operation
 */
class OperationFormatMetadata extends OperationAbstract
{
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
        $this->writeMetadataIntoOutputFolder($nft->getTokenID(), $this->formatMetadata($nft));
    }

    /**
     * @param NFT $nft
     * @return array
     */
    private function formatMetadata(NFT $nft): array
    {
        $originalMetadata = $nft->getMetadata();

        /*
         * handle most important metadata
         */
        foreach (['name', 'description', 'image', 'attributes'] as $key) {
            if (!array_key_exists($key, $originalMetadata)) {
                continue;
            }

            $output[$key] = $originalMetadata[$key];
            unset($originalMetadata[$key]);
        }

        /*
         * attach all others metadata
         */
        foreach (array_keys($originalMetadata) as $key) {
            $output[$key] = $originalMetadata[$key];
        }

        /*
         * order attributes alphabetically
         */
        usort($output['attributes'], function (array $attributeA, array $attributeB) {
            $traitTypeA = $attributeA['trait_type'];
            $traitTypeB = $attributeB['trait_type'];


            return ($traitTypeA === $traitTypeB) ? 0 : ($traitTypeA < $traitTypeB ? -1 : 1);
        });

        /*
         * return
         */
        return $output;
    }
}
