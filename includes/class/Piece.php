<?php

/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 14:15
 */
class Piece
{
    /**
     * @var array|Track[]
     */
    public $tracks = [];

    /**
     * @var string
     */
    public $name;

    /**
     * @param Track $track
     */
    public function addTrack($track){

        if ($track->name != $this->name) {
            throw new InvalidArgumentException('Track name dis-matched! Expected "'. $this->name. ', given "'. $track->name. '".');
        }

        foreach ($this->tracks as $t) {
            if ($t->layer == $track->layer) {
                throw new InvalidArgumentException('There are already a track on layer '. $t->layer. ' in piece '. $this->name);
            }
        }
        $this->tracks[] = $track;
    }

    /**
     * @return int
     */
    public function getTrackNum()
    {
        return count($this->tracks);
    }
}