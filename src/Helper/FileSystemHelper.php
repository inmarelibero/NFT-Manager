<?php

namespace Inmarelibero\NFTManager\Helper;

use Inmarelibero\NFTManager\Exception\AppException;
use Inmarelibero\NFTManager\Exception\FileNotFoundException;

/**
 * Class FileSystemHelper
 * @package Inmarelibero\NFTManager\Helper
 */
class FileSystemHelper
{
    /**
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
     * @param string $absolutePath
     * @throws AppException
     */
    public static function createDirectory(string $absolutePath)
    {
        if (!mkdir($absolutePath) && !is_dir($absolutePath)) {
            throw new AppException(sprintf('Directory "%s" was not created', $absolutePath));
        }
    }

    /**
     * @param string $absolutePath
     * @param false|bool $keepEmptyFolder set to true if you want to delete only the whole content of $absolutePath but leave the empty folder
     * @throws AppException
     */
    public static function deleteDirectory(string $absolutePath, bool $keepEmptyFolder = false): void
    {
        if (!file_exists($absolutePath)) {
            return;
        }

        if (!is_dir($absolutePath)) {
            throw new AppException(sprintf('Unable to delete directory "%s": it is not a directory', $absolutePath));
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
                throw new AppException(sprintf('Unable to delete directory "%s"', $absolutePath));
            }
        }
    }

    /**
     * @param string $absolutePath
     * @throws AppException
     * @throws FileNotFoundException
     */
    public static function deleteFile(string $absolutePath)
    {
        if (!file_exists($absolutePath)) {
            throw new FileNotFoundException(sprintf('Unable to delete file "%s": file does not exist.', $absolutePath));
        }

        if (!unlink($absolutePath)) {
            throw new AppException(sprintf('Unable to delete file "%s".', $absolutePath));
        }
    }

    /**
     * @param string $dir
     * @param array|string[] $extensions
     * @return array
     * @throws AppException
     */
    public static function getFilesInDir(string $dir): array
    {
        if (!file_exists($dir)) {
            throw new AppException(sprintf('Unable to get files in directory "%s": directory does not exist.', $dir));
        }

        $output = scandir($dir);

        if ($output === false) {
            throw new AppException(sprintf('Unable to scan directory "%s"', $dir));
        }

        $output = array_filter($output, function (string $filename) {
            if (preg_match('#^\..*#', $filename)) {
                return false;
            }

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
     * @param string $from
     * @param string $to
     * @throws \Exception
     */
    public static function deepCopy(string $from, string $to)
    {
        $from = rtrim($from, '/') . '/';
        $to = rtrim($to, '/') . '/';

        // (A1) SOURCE FOLDER CHECK
        if (!is_dir($from)) {
            exit("$from does not exist");
        }

        // (A2) CREATE DESTINATION FOLDER
        if (!is_dir($to)) {
            if (!mkdir($to)) {
                exit("Failed to create $to");
            };
//            echo "$to created\r\n";
        }

        // (A3) COPY FILES + RECURSIVE INTERNAL FOLDERS
        $dir = opendir($from);
        while (($ff = readdir($dir)) !== false) {
            if ($ff!="." && $ff!="..") {
                if (is_dir("$from$ff")) {
                    self::deepCopy("$from$ff/", "$to$ff/");
                } else {
                    if (!copy("$from$ff", "$to$ff")) {
                        throw new \Exception("Error copying $from$ff to $to$ff");
                    }
//                echo "$from$ff copied to $to$ff\r\n";
                }
            }
        }

        closedir($dir);
    }

    /**
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
