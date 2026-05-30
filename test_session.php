<?php
session_start();
$orig = session_id();
$_SESSION['foo'] = 'bar';
session_write_close();

// Now destroy orig
session_id($orig);
session_start();
session_destroy();
session_write_close();

// Re-open orig
session_id($orig);
session_start();
var_dump($_SESSION); // should be empty
