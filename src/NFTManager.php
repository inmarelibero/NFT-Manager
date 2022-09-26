<?php

namespace Inmarelibero\NFTManager;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Helper\FileSystemHelper;
use Inmarelibero\NFTManager\Operation\OperationInterface;

/**
 * Class NFTManager
 * @package Inmarelibero\NFTManager
 */
class NFTManager extends NFTManagerAbstract
{
    /**
     * NFTManager constructor.
     * @param string $projectRoot
     * @throws AppException
     */
    public function __construct()
    {
        $projectRoot = FileSystemHelper::getRunScriptDirectory();

        // set project root
        $this->setProjectRoot($projectRoot);

        // initialize output folder
        $this->initOutputFolders();
    }

    /**
     * @param string $operationClass
     * @param array $operationOptions
     */
    public function run(string $operationClass, array $operationOptions = []): void
    {
        /*
         *
         */
        echo PHP_EOL;
        echo '========================='.PHP_EOL;
        echo 'Executing operation #'.$this->getIndexOfExecutingOperation();
        echo PHP_EOL;
        echo '========================='.PHP_EOL;
        echo PHP_EOL;

        /** @var OperationInterface $operation */
        $operation = new $operationClass($this);

        try {
            $this->performOperation($operation, $operationOptions);
        } catch (AppException $exception) {
            echo $exception->getMessage();
        }

        echo PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * @param OperationInterface $operation
     * @param array $options
     * @throws AppException
     */
    private function performOperation(OperationInterface $operation, array $options = [])
    {
        /**
         *
         */
        $this->preExecuteOperation();

        $nfts = $this->generateNFTs();

        $nfts = $operation->preExecute($nfts, $options);

        foreach ($nfts as $i => $nft) {
            $tokenId = $nft->getTokenID();

            $options['iteration_index'] = $i;
            $options = $this->resolveOptions($operation, $options);

            /*
             * skip if necessary
             */
            if (
                ($options['from_id'] !== null && $tokenId < $options['from_id'])
                ||
                ($options['to_id'] !== null && $tokenId > $options['to_id'])
            ) {
                $operation->onSkip($nft);
                continue;
            }


            /*
             *
             */
            $operation->execute($nft, $options);
        }

        $this->postExecuteOperation($operation);
    }

    /**
     * @param OperationInterface $operation
     * @param array $options
     * @return array
     * @throws AppException
     */
    private function resolveOptions(OperationInterface $operation, array $options = []): array
    {
        $optionsResolver = $operation->configureOptions();
        $dd = $optionsResolver->getDefinedOptions();

        /*
         * add "iteration_index" to handled options
         */
        if (array_key_exists('iteration_index', $dd)) {
            throw new AppException('Define on Operation an option called "iteration_index" is forbidden.');
        }

        $optionsResolver->setDefault('iteration_index', null);
        $optionsResolver->setAllowedTypes('iteration_index', ['int']);

        /*
         * add "from_id" to handled options
         *
         * skip NFTs with ID less than this value
         */
        if (array_key_exists('from_id', $dd)) {
            throw new AppException('Define on Operation an option called "from_id" is forbidden.');
        }

        $optionsResolver->setDefault('from_id', null);
        $optionsResolver->setAllowedTypes('from_id', ['null', 'int']);

        /*
         * add "to_id" to handled options
         *
         * skip NFTs with ID greater than this value
         */
        if (array_key_exists('to_id', $dd)) {
            throw new AppException('Define on Operation an option called "to_id" is forbidden.');
        }

        $optionsResolver->setDefault('to_id', null);
        $optionsResolver->setAllowedTypes('to_id', ['null', 'int']);

        return $optionsResolver->resolve($options);
    }
}
