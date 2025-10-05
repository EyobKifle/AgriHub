<?php
require_once 'php/config.php';

if ($conn) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
