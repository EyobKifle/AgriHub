# TODO: Convert all paths to absolute

## PHP Includes
- Change require '../php/config.php' to require_once __DIR__ . '/../php/config.php' in php/Farming-Guidance.php
- Change include '../HTML/User-Management.html' to include __DIR__ . '/../HTML/User-Management.html' in php/User-Management.php

## Web Paths in PHP files (href, src, Location)
- Replace href="../Css/" with href="/AgriHub/Css/" in all php/ files
- Replace src="../Js/" with src="/AgriHub/Js/" in all php/ files
- Replace Location: ../HTML/ with Location: /AgriHub/HTML/ in all php/ files
- Replace src="../uploads/" with src="/AgriHub/uploads/" in all php/ files
- Replace src="../images/" with src="/AgriHub/images/" in all php/ files
- Also for any "Css/" without ../ to "/AgriHub/Css/" etc.

## Web Paths in HTML files
- Similar replacements in HTML/ files

## Web Paths in JS files
- Replace '../php/' with '/AgriHub/php/' in fetch
- Replace '../images/' with '/AgriHub/images/' in src

## Test
- Launch browser and check if pages load correctly
