<?php
include "../vendor/autoload.php";
include "../src/kuzzle.php";
include "../src/document.php";
include "../src/dataMapping.php";
include "../src/dataCollection.php";
include "../src/memoryStorage.php";
include "../src/security/security.php";
include "../src/security/role.php";
include "../src/security/profile.php";
include "../src/security/user.php";

$kuzzle = new \Kuzzle\Kuzzle('http://localhost:7511');
$response = $kuzzle->query(
    [
        'index' => 'myindex',
        'collection' => 'mycollection',
        'controller' => 'write',
        'action' => 'create'
    ],
    [
        'body' => ['foo' => 'bar']
    ]
);
var_dump($response);