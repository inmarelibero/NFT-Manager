<?php

namespace Inmarelibero\NFTManager;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Exception\FilesystemException;
use Inmarelibero\NFTManager\Helper\FileSystemHelper;
use Inmarelibero\NFTManager\Model\NFT;
use Inmarelibero\NFTManager\Operation\OperationInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NFTManagerAbstract
 * @package Inmarelibero\NFTManager
 */
abstract class NFTManagerAbstract
{
    /**
     * absolute path
     * WITHOUT trailing "/"
     *
     * @var string
     */
    private $projectRoot;

    /**
     * relative path of the "input folder" (containing "images/" and "metadata/" subfolders)
     * WITHOUT trailing "/"
     *
     * @var string
     */
    private $inputRelativePath;

    /**
     * relative path of the "images/" subfolder containing the NFT images
     * WITHOUT trailing "/"
     *
     * @var string
     */
    private $imagesRelativePath;

    /**
     * relative path of the "images/" subfolder containing the NFT metadata
     * WITHOUT trailing "/"
     *
     * @var string
     */
    private $metadataRelativePath;

    /**
     * Callable that will be called after reading the file containing the metadata and before
     * parsing the content as a JSON
     *
     * @var callable|null
     */
    private $metadataParserCallable;

    /*
     * how many operations have been finalized
     */
    private $executedOperations = 0;

    /**
     * @param string $projectRoot
     * @param string $inputRelativePath
     * @param string $imagesRelativePath
     * @param string $metadataRelativePath
     * @param callable|null $metadataParserCallable
     * @throws AppException
     */
    private function __construct(string $projectRoot, string $inputRelativePath, string $imagesRelativePath, string $metadataRelativePath, callable $metadataParserCallable = null)
    {
        // set project root
        $this->setProjectRoot($projectRoot);

        // set relative paths
        $this->setInputRelativePath($inputRelativePath);
        $this->setImagesRelativePath($imagesRelativePath);
        $this->setMetadataRelativePath($metadataRelativePath);

        $this->setMetadataParserCallable($metadataParserCallable);


        // initialize output folder
        $this->initOutputFolders();
    }

    /**
     * @param array $options
     * @return static
     */
    public static function build(array $options = [])
    {
        $options = (new OptionsResolver())
            ->setDefaults([
                /**
                 * relative (to the folder containing run.php) path containing the input assets
                 * this folder must contain subfolders "metadata" and "images"
                 */
                'input_relative_path' => 'input/',

                /**
                 * relative (to "input" folder) path containing the NFT images
                 */
                'images_relative_path' => 'images/',

                /**
                 * relative (to "input" folder) path containing the NFT metadata
                 */
                'metadata_relative_path' => 'metadata/',

                /**
                 * specify, if necessary, a callable that will be called after reading the file containing the metadata and before
                 * parsing the content as a JSON
                 *
                 * eg. a callable to replace ' with "
                 */
                'metadata_parser_callable' => null,
            ])
            ->setAllowedTypes('input_relative_path', ['string'])
            ->setAllowedTypes('images_relative_path', ['string'])
            ->setAllowedTypes('metadata_relative_path', ['string'])
            ->setAllowedTypes('metadata_parser_callable', ['null', 'callable'])
        ->resolve($options);

        $projectRoot = FileSystemHelper::getRunScriptDirectory();

        return new static($projectRoot, $options['input_relative_path'], $options['images_relative_path'], $options['metadata_relative_path'], $options['metadata_parser_callable']);
    }

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

        $this->projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
    }

    /**
     * @return string
     */
    protected function getInputRelativePath(): string
    {
        return $this->inputRelativePath;
    }

    /**
     * @param string $inputRelativePath
     * @throws AppException
     */
    protected function setInputRelativePath(string $inputRelativePath): void
    {
        $this->inputRelativePath = rtrim($inputRelativePath, DIRECTORY_SEPARATOR);
    }

    /**
     * @return string
     */
    public function getImagesRelativePath(): string
    {
        return $this->imagesRelativePath;
    }

    /**
     * @param string $imagesRelativePath
     */
    protected function setImagesRelativePath(string $imagesRelativePath): void
    {
        $this->imagesRelativePath = rtrim($imagesRelativePath, DIRECTORY_SEPARATOR);;
    }

    /**
     * @return string
     */
    public function getMetadataRelativePath(): string
    {
        return $this->metadataRelativePath;
    }

    /**
     * @param string $metadataRelativePath
     */
    protected function setMetadataRelativePath(string $metadataRelativePath): void
    {
        $this->metadataRelativePath = rtrim($metadataRelativePath, DIRECTORY_SEPARATOR);;
    }

    /**
     * @return callable|null
     */
    public function getMetadataParserCallable(): ?callable
    {
        return $this->metadataParserCallable;
    }

    /**
     * @param callable|null $metadataParserCallable
     */
    protected function setMetadataParserCallable(?callable $metadataParserCallable): void
    {
        $this->metadataParserCallable = $metadataParserCallable;
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
     * and this temporary folder is used as input
     *
     * @return string
     */
    protected function getOperationInputFolder(): string
    {
        if ($this->isBeforeFirstOperation()) {
            $absoluteInputPath = $this->getProjectRoot() . DIRECTORY_SEPARATOR . $this->getInputRelativePath();

            if (!file_exists($absoluteInputPath)) {
                throw new AppException(sprintf('Unable to set directory "%s" as input dir: directory does not exist.', $absoluteInputPath));
            }

            return $absoluteInputPath;
        }

        return $this->getProjectRoot().'/.input-tmp';
    }

    /**
     * @return string
     */
    protected function getImagesOperationInputFolder(): string
    {
        $relativePath = 'images/';

        if ($this->isBeforeFirstOperation()) {
            $relativePath = $this->getImagesRelativePath();
        }

        return $this->getOperationInputFolder() . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * @return string
     */
    protected function getMetadataOperationInputFolder(): string
    {
        $relativePath = 'metadata/';

        if ($this->isBeforeFirstOperation()) {
            $relativePath = $this->getMetadataRelativePath();
        }

        return $this->getOperationInputFolder() . DIRECTORY_SEPARATOR . $relativePath;
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
            $metadataPath = $metadataPathsArray[$k];

            if (!file_exists($metadataPath)) {
                throw new AppException(sprintf('Unable to find file "%s".', $metadataPath));
            }

            $fileContent = file_get_contents($metadataPath);

            if (is_callable($this->getMetadataParserCallable())) {
                $fileContent = $this->getMetadataParserCallable()($fileContent);
            }

            $metadata = json_decode($fileContent, true);

            if (!$metadata) {
                throw new AppException(sprintf('Unable to decode metadata contained in file "%s": %s.', $metadataPath, json_last_error_msg()));
            }

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
