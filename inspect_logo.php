<?php
$img = imagecreatefromjpeg('public/images/guidance-logo.png');
$rgb = imagecolorat($img, 0, 0);
$colors = imagecolorsforindex($img, $rgb);
printf("Top-left color: rgba(%d, %d, %d, %.2f)\n", $colors['red'], $colors['green'], $colors['blue'], $colors['alpha']);
$c2 = imagecolorsforindex($img, imagecolorat($img, 250, 5));
printf("Top center: rgb(%d, %d, %d)\n", $c2['red'], $c2['green'], $c2['blue']);
