<?php

namespace App\Utils;

class Utilities {
    public static function sortCollection($collection, $function) {
        $iterator = $collection->getIterator();
        $iterator->uasort($function);
        return $iterator;
    }
}