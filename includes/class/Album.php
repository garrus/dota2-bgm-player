<?php

/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 14:14
 */
class Album
{

    /**
     * @var array|Piece[]
     */
    public $pieces = [];

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $namePrefix;

    /**
     * @var string
     */
    public $path;


    /**
     * @param Track $track
     */
    public function addTrack($track) {

        if (isset($this->pieces[$track->name])) {
            $this->pieces[$track->name]->addTrack($track);
        } else {
            $piece = new Piece();
            $piece->name = $track->name;
            $piece->addTrack($track);
            $this->pieces[$track->name] = $piece;
        }
    }

}