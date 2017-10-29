<?php
require 'cronable.php';

use \Maikuolan\Cronable\Cronable;

/** Instantiate the Cronable class to an object. */
$Cronable = new Cronable;

/** Create a tast to update CIDRAM. */
$Cronable->createTask('CIDRAM', 'username', 'password', 'http://foo.tld/cidram/loader.php');

/** Create a tast to update phpMussel. */
$Cronable->createTask('phpMussel', 'username', 'password', 'http://foo.tld/phpmussel/loader.php');

/** Execute all tasks. */
$Cronable->execute();

/** Print output for cron. */
echo $Cronable->Output;
