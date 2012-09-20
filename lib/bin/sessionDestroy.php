<?php
/* @var $arguments array */
$arguments = isset($arguments) ? $arguments : array();
session_id($arguments[0]);
session_start();
session_destroy();