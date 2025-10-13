<?php

/*
 * @version  UP-2.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */
 
 defined('_JEXEC') or die;

function text_sort_asc($a, $b) {
    return $a["text"] > $b["text"];
}

function text_sort_desc($a, $b) {
    return $a["text"] < $b["text"];
}
