<?php

namespace mito\assets;

use Yii;
use \yii\helpers\FileHelper;

/**
 * Modified version of AssetManager.
 * Places asset versions together into subdirectory.
 */
class AssetManager extends \yii\web\AssetManager
{
    protected function hash2($dir, $time)
    {
        return [$this->hash($dir), $this->hash($time)];
    }

    /**
     * Publishes a file.
     * @param string $src the asset file to be published
     * @return array the path and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishFile($src)
    {
        list($dir, $subdir) = $this->hash2(dirname($src), filemtime($src));
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $subdir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($dstDir)) {
            FileHelper::createDirectory($dstDir, $this->dirMode, true);
        }

        if ($this->linkAssets) {
            if (!is_file($dstFile)) {
                symlink($src, $dstFile);
            }
        } elseif (@filemtime($dstFile) < @filemtime($src)) {
            copy($src, $dstFile);
            if ($this->fileMode !== null) {
                @chmod($dstFile, $this->fileMode);
            }
        }

        return [$dstFile, $this->baseUrl . "/$dir/$subdir/$fileName"];
    }

    /**
     * Publishes a directory.
     * @param string $src the asset directory to be published
     * @param array $options the options to be applied when publishing a directory.
     * The following options are supported:
     *
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   This overrides [[beforeCopy]] if set.
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied.
     *   This overrides [[afterCopy]] if set.
     * - forceCopy: boolean, whether the directory being published should be copied even if
     *   it is found in the target directory. This option is used only when publishing a directory.
     *   This overrides [[forceCopy]] if set.
     *
     * @return array the path directory and the URL that the asset is published as.
     * @throws InvalidParamException if the asset to be published does not exist.
     */
    protected function publishDirectory($src, $options)
    {
        list($dir, $subdir) = $this->hash2($src, filemtime($src));
        $baseDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstDir = $baseDir . DIRECTORY_SEPARATOR . $subdir;

        if (!is_dir($baseDir)) {
            FileHelper::createDirectory($baseDir, $this->dirMode, true);
        }

        if ($this->linkAssets) {
            if (!is_dir($dstDir)) {
                symlink($src, $dstDir);
            }
        } elseif (!is_dir($dstDir) || !empty($options['forceCopy']) || (!isset($options['forceCopy']) && $this->forceCopy)) {
            $opts = [
                'dirMode' => $this->dirMode,
                'fileMode' => $this->fileMode,
            ];
            if (isset($options['beforeCopy'])) {
                $opts['beforeCopy'] = $options['beforeCopy'];
            } elseif ($this->beforeCopy !== null) {
                $opts['beforeCopy'] = $this->beforeCopy;
            } else {
                $opts['beforeCopy'] = function ($from, $to) {
                    return strncmp(basename($from), '.', 1) !== 0;
                };
            }
            if (isset($options['afterCopy'])) {
                $opts['afterCopy'] = $options['afterCopy'];
            } elseif ($this->afterCopy !== null) {
                $opts['afterCopy'] = $this->afterCopy;
            }
            FileHelper::copyDirectory($src, $dstDir, $opts);
        }

        return [$dstDir, $this->baseUrl . '/' . $dir . '/' . $subdir];
    }

    /**
     * Returns the published path of a file path.
     * This method does not perform any publishing. It merely tells you
     * if the file or directory is published, where it will go.
     * @param string $path directory or file path being published
     * @return string the published file path. False if the file or directory does not exist
     */
    public function getPublishedPath($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            $base = $this->basePath . DIRECTORY_SEPARATOR;
            if (is_file($path)) {
                return $base . implode(DIRECTORY_SEPARATOR, $this->hash2(dirname($path), filemtime($path))) . DIRECTORY_SEPARATOR . basename($path);
            } else {
                return $base . implode(DIRECTORY_SEPARATOR, $this->hash2($path, filemtime($path)));
            }
        } else {
            return false;
        }
    }

    /**
     * Returns the URL of a published file path.
     * This method does not perform any publishing. It merely tells you
     * if the file path is published, what the URL will be to access it.
     * @param string $path directory or file path being published
     * @return string the published URL for the file or directory. False if the file or directory does not exist.
     */
    public function getPublishedUrl($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            if (is_file($path)) {
                return $this->baseUrl . '/' . implode('/', $this->hash2(dirname($path), filemtime($path))) . '/' . basename($path);
            } else {
                return $this->baseUrl . '/' . implode('/', $this->hash2($path, filemtime($path)));
            }
        } else {
            return false;
        }
    }

    /**
     * Clean up old asset versions.
     * @param string|null $path assets path or alias, if null, Yii::$app->assetManager->basePath is used.
     * @param integer $keep how many old versions to keep
     */
    public static function cleanup($path = null, $keep = 0)
    {
        $keep++;
        if ($path === null) {
            $path = Yii::$app->assetManager->basePath;
        }
        $path = Yii::getAlias($path);
        if (!is_dir($path)) {
            return;
        }

        $dir = opendir($path);

        while (false !== ($item = readdir($dir))) {
            if ($item === '.' || $item === '..' || !is_dir($path . DIRECTORY_SEPARATOR . $item) || is_link($path . DIRECTORY_SEPARATOR . $item)) {
                continue;
            }
            $subdir = opendir($path . DIRECTORY_SEPARATOR . $item);
            $versions = [];
            while (false !== ($version = readdir($subdir))) {
                if ($version === '.' || $version === '..') {
                    continue;
                }
                $versionPath = $path . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . $version;
                $stat = lstat($versionPath);
                $versions[$versionPath] = $stat['mtime'];
            }
            arsort($versions);
            $versions = array_slice($versions, $keep);
            foreach ($versions as $version => $time) {
                if (!is_link($version) && is_dir($version)) {
                    CFileHelper::removeDirectory($version);
                } else {
                    @unlink($version);
                }
            }
            closedir($subdir);
        }
        closedir($dir);
    }
}
