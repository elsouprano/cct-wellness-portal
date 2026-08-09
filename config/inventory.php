<?php

$path = resource_path('data/inventory-items.json');

if (file_exists($path)) {
    return json_decode(file_get_contents($path), true);
}

return [];
