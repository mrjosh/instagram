<?php

/**
 * @author : Alireza Josheghani <josheghani.dev@gmail.com>
 * @version : 1.0
 * @package Instagram
 * */

use Instagram\Instagram;

require __DIR__.'/vendor/autoload.php';

$instagram = new Instagram();
$instagram->login([
    'username' => $_POST['username'],
    'password' => $_POST['password']
]);

$results = $instagram->upload([
    'caption' => $_POST['caption'],
    'tmp_image' => $_FILES['image']['tmp_name']
]);

var_dump($results);