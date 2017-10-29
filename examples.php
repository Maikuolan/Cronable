<?php
/**
 * This file provides some basic examples of how to use Cronable.
 *
 * You could instantiate an object from the Cronable class directly within the
 * class file itself if you wanted to, perhaps for the sake of simplicity, or,
 * if you'd prefer to maintain good SoC and avoid overriding changes during
 * Composer updates, you could do as is done by this example file, by requiring
 * the class file and importing the class from its namespace into a new,
 * separate PHP file, and instantiating the object there.
 */

/** Requiring the Cronable class file. */
require __DIR__ . 'src/Cronable.php';

/** Importing the Cronable class from its namespace. */
use \Maikuolan\Cronable\Cronable;

/** Instantiate a new object from the Cronable class. */
$Cronable = new Cronable;

/**
 * Create a task to update CIDRAM.
 *
 * The createTask method accepts 4 parameters:
 * - The type of package to be updated (must always be "CIDRAM" for updating CIDRAM).
 * - The username of the front-end account to be used by Cronable for updating the package.
 * - The password of the front-end account to be used by Cronable for updating the package.
 * - The location of the package loader file.
 */
$Cronable->createTask('CIDRAM', 'username', 'password', 'http://foo.tld/cidram/loader.php');

/**
 * Create a task to update phpMussel.
 *
 * The createTask method accepts 4 parameters:
 * - The type of package to be updated (must always be "phpMussel" for updating phpMussel).
 * - The username of the front-end account to be used by Cronable for updating the package.
 * - The password of the front-end account to be used by Cronable for updating the package.
 * - The location of the package loader file.
 */
$Cronable->createTask('phpMussel', 'username', 'password', 'http://foo.tld/phpmussel/loader.php');

/** Execute all tasks. */
$Cronable->execute();

/**
 * Print output for cron.
 *
 * This should be done as so that your cronjob can properly report whether
 * updating was successful, and which components were updated accordingly.
 *
 * Output should either be a list of which components were updated, separated
 * by their relevant associated tasks, or the relevant associated error
 * messages, if problems occurred while attempting to update.
 */
echo $Cronable->Output;
