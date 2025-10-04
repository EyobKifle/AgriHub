<?php
session_start();
session_unset();
session_destroy();
header('Location: ../HTML/Login.html?logged_out=1');
exit();
