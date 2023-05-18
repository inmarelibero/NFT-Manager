<?php

namespace Inmarelibero\NFTManager\Helper;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Exception\FilesystemException;

/**
 * Class FileSystemHelper
 * @package Inmarelibero\NFTManager\Helper
 */
class FileSystemHelper
{
    /**
     * @return string
     */
    public static function getRunScriptDirectory(): string
    {
        $pwd = $_SERVER['PWD'];
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'];

        $absolutePath = $pwd.DIRECTORY_SEPARATOR.$scriptFilename;
        $tokens = explode(DIRECTORY_SEPARATOR, $absolutePath);
        array_pop($tokens);

        return implode(DIRECTORY_SEPARATOR, $tokens);
    }

    /**
     * Delete (if existing) a directory and its content, and then recreate it empty
     *
     * @param string $absolutePath
     * @throws AppException
     */
    public static function deleteAndCreateDirectory(string $absolutePath)
    {
        if (file_exists($absolutePath)) {
            self::deleteDirectory($absolutePath);
        }

        self::createDirectory($absolutePath);
    }

    /**
     * Create a directory, throw exception if it's already existing
     *
     * @param string $absolutePath
     * @throws FilesystemException
     */
    public static function createDirectory(string $absolutePath)
    {
        if (file_exists($absolutePath)) {
            throw new FilesystemException(sprintf('Directory "%s" was not created', $absolutePath));
        }

        if (!mkdir($absolutePath) && !is_dir($absolutePath)) {
            throw new FilesystemException(sprintf('Directory "%s" was not created', $absolutePath));
        }
    }

    /**
     * Delete a directory and its content recursively
     *
     * @param string $absolutePath
     * @param false|bool $keepEmptyFolder set to true if you want to delete only the whole content of $absolutePath but leave the empty folder
     * @throws FilesystemException
     */
    public static function deleteDirectory(string $absolutePath, bool $keepEmptyFolder = false): void
    {
        if (!file_exists($absolutePath)) {
            return;
        }

        if (!is_dir($absolutePath)) {
            throw new FilesystemException(sprintf('Unable to delete directory "%s": it is not a directory', $absolutePath));
        }

        foreach (scandir($absolutePath) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $absolutePath . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                self::deleteDirectory($itemPath);
            } elseif (is_file($itemPath)) {
                self::deleteFile($itemPath);
            }
        }

        if ($keepEmptyFolder !== true) {
            if (!rmdir($absolutePath)) {
                throw new FilesystemException(sprintf('Unable to delete directory "%s"', $absolutePath));
            }
        }
    }

    /**
     * Delete a single file
     *
     * @param string $absolutePath
     * @throws FilesystemException
     */
    public static function deleteFile(string $absolutePath)
    {
        if (!file_exists($absolutePath)) {
            throw new FilesystemException(sprintf('Unable to delete file "%s": file does not exist.', $absolutePath));
        }

        if (!unlink($absolutePath)) {
            throw new FilesystemException(sprintf('Unable to delete file "%s".', $absolutePath));
        }
    }

    /**
     * Return an array with all the absolute paths of the files contained in a directory (not recursively)
     *
     * @param string $dir the directory to scan
     * @return string[]
     * @throws FilesystemException
     */
    public static function getFilesInDir(string $dir): array
    {
        if (!file_exists($dir)) {
            throw new FilesystemException(sprintf('Unable to get files in directory "%s": directory does not exist.', $dir));
        }

        $output = scandir($dir);

        if ($output === false) {
            throw new FilesystemException(sprintf('Unable to scan directory "%s"', $dir));
        }

        $output = array_filter($output, function (string $filename) {
            // ignore files beginning with "."
            if (preg_match('#^\..*#', $filename)) {
                return false;
            }

            // ignore directories
            if (is_dir($filename)) {
                return false;
            }

            return true;
        });

        asort($output, SORT_NUMERIC);

        $output = array_combine(range(0, count($output)-1), $output);

        // build absolute path
        array_walk($output, function (&$item) use ($dir) {
            $item = sprintf('%s/%s', $dir, $item);
        });

        return $output;
    }

    /**
     * Copy recursively a directory into another
     *
     * @param string $from
     * @param string $to
     * @throws FilesystemException
     */
    public static function deepCopy(string $from, string $to)
    {
        $from = rtrim($from, '/') . '/';
        $to = rtrim($to, '/') . '/';

        // (A1) SOURCE FOLDER CHECK
        if (!is_dir($from)) {
            throw new FilesystemException("$from does not exist");
        }

        // (A2) CREATE DESTINATION FOLDER
        if (!is_dir($to)) {
            if (!mkdir($to)) {
                throw new FilesystemException("Failed to create $to");
            };
        }

        // (A3) COPY FILES + RECURSIVE INTERNAL FOLDERS
        $dir = opendir($from);
        while (($ff = readdir($dir)) !== false) {
            if ($ff!="." && $ff!="..") {
                if (is_dir("$from$ff")) {
                    self::deepCopy("$from$ff/", "$to$ff/");
                } else {
                    if (!copy("$from$ff", "$to$ff")) {
                        throw new FilesystemException("Error copying $from$ff to $to$ff");
                    }
                }
            }
        }

        closedir($dir);
    }

    /**
     * Convert $content into JSON format and write it in a file
     *
     * @param string $absolutePath
     * @param array $content
     */
    public static function writeJSONIntoFile(string $absolutePath, array $content)
    {
        file_put_contents(
            $absolutePath,
            json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }
}
