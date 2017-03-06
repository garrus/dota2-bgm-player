<?php
/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 16:24
 */

if (php_sapi_name() !== 'cli') {
    die('Only run in cli mode.');
}

require __DIR__. '/includes/common.php';

$albums = AlbumFactory::createAlbumsInDirectory(__DIR__. DS. 'album');
$content = serialize($albums);
//if ($content !== @file_get_contents(__DIR__. DS. 'albums.bin')) {
    file_put_contents(__DIR__. DS. 'albums.bin', $content);
//}