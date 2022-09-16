<?php

namespace Inmarelibero\NFTManager\Operation;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Model\NFT;
use Inmarelibero\NFTManager\NFTManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Interface OperationInterface
 * @package Inmarelibero\NFTManager\Operation
 */
interface OperationInterface
{
    /**
     * OperationInterface constructor.
     * @param NFTManager $nftHandler
     */
    public function __construct(NFTManager $nftHandler);

    /**
     * @param NFT $nft
     * @param array $options
     * @throws AppException
     */
    public function execute(NFT $nft, array $options = []): void;

    /**
     * @return OptionsResolver
     */
    public function configureOptions(): OptionsResolver;

    /**
     * @param NFT $nft
     */
    public function onSkip(NFT $nft): void;
}
