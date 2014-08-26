<?php
session_start();
$_SESSION['test'] = 1;
session_destroy();
