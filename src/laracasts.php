<?php

require __DIR__ . '/../vendor/autoload.php';
require './workflows.php';

$query = "{query}";
$w     = new Workflows;

echo $w->search($query);