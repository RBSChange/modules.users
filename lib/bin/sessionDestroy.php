<?php
session_id($_POST['argv'][0]);
session_start();
session_destroy();