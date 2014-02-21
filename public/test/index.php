<?php
$name = 'a.1231231';
if (strpos($name, '.') !== false) {
	if (!preg_match("/\.[a-z]{1}/i", $name)) {
		echo 'false';
	}
	else {
		echo 'true';
	}
}
