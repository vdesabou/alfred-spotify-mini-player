<?php

require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$current_search_order_list = $argv[1];

$all_categories = 'playlist▹track▹artist▹album▹show▹episode';
$categories = explode('▹', $all_categories);
$current = explode('▹', $current_search_order_list);

foreach ($categories as $category) {

    if (strpos($current_search_order_list, $category) !== false) {

    } else {
        if($current_search_order_list != "") {
            $output = $current_search_order_list.'▹'.$category;
            $nb = count($current)+1;
        } else {
            $output = $category;
            $nb = 1;
        }
        $w->result(null, $output, $category,array(
            'Select as result #'.$nb,
            'alt' => '',
            'cmd' => '',
            'shift' => '',
            'fn' => '',
            'ctrl' => '',
        ), './images/'.$category.'s.png', 'yes', null, '');
    }
}

echo $w->tojson();
