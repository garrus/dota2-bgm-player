<?php

/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 14:17
 */
class AlbumFactory
{

    private static $finfo;

    /**
     * @return resource
     */
    private static function getFileInfo()
    {
        return static::$finfo ?: (static::$finfo = finfo_open(FILEINFO_MIME_TYPE));
    }

    /**
     * @param $path
     * @return array|Album[]
     */
    public static function createAlbumsInDirectory($path)
    {
        $albums = [];
        foreach (new DirectoryIterator($path) as $dir) {
            if ($dir->isDir() && !$dir->isDot()) {
                $album = static::createAlbum($dir->getPathname());
                if (count($album->pieces)) {
                    $albums[] = $album;
                }
            }
        }
        return $albums;
    }

    /**
     * @param $path
     * @return Album
     */
    public static function createAlbum($path)
    {
        $manifest = Manifest::createFromFile($path . DS . 'manifest.json');

        $album = new Album();
        $album->path = $path;
        $album->name = $manifest->albumName ?: basename($path);

        $finfo = static::getFileInfo();
        foreach (new DirectoryIterator($path) as $file) {
            if ($file->isDir() || $file->isDir()) {
                continue;
            }

            $mimeType = finfo_file($finfo, $file->getPathname());
            if (strpos($mimeType, 'audio/') === false) {
                continue;
            }

            $track = static::createTrack($file->getBasename(), $manifest->namePrefix);
            $album->addTrack($track);
        }

        return $album;
    }

    /**
     * @param string $filename
     * @param string $prefix
     * @return Track
     */
    public static function createTrack($filename, $prefix)
    {

        $track = new Track;

        if (is_string($prefix) && mb_strlen($prefix) > 0) {
            $filename = mb_substr($filename, mb_strlen($prefix));
        }
        $track->rawFilename = $prefix. $filename;
        $track->trimFilename = $filename;

        if (preg_match('/^(.+?) layer ?(\d+)/i', $filename, $matches)) {
            $track->name = ucfirst(trim($matches[1]));
            $track->layer = (int)$matches[2];
        } else {
            $track->name = substr($filename, 0, strrpos($filename, '.'));
            $track->layer = 0;
        }

        return $track;
    }

}