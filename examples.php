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
require __DIR__ . '/src/Cronable.php';

/** Importing the Cronable class from its namespace. */
use \Maikuolan\Cronable\Cronable;

/** Instantiate a new object from the Cronable class. */
$Cronable = new Cronable;

/** ---------- Next, create your tasks, and then execute them, with... ---------- */

/**
 * Create a task to update CIDRAM or phpMussel.
 *
 * The createTask method accepts 5 parameters:
 * - The type of package to be updated ('CIDRAM' or 'phpMussel').
 * - The username of the front-end account to be used by Cronable for updating the package.
 * - The password of the front-end account to be used by Cronable for updating the package.
 * - The location of the package loader file.
 * - What to update (specify 'Signatures' to update just signatures; or anything else to update everything).
 *
 * Examples:
 */
$Cronable->createTask('CIDRAM', 'username', 'password', 'http://foo.tld/cidram/loader.php', 'Signatures');
$Cronable->createTask('phpMussel', 'username', 'password', 'http://foo.tld/phpmussel/loader.php', 'Everything');

/** After you've created your tasks, you'll want to execute them all. */
$Cronable->execute();

/** ---------- Alternatively, to update your package locally (Cronable >= v1.1.0)... ---------- */

/**
 * For this, you don't need to separate methods (createTask, execute, etc).
 * Everything is done in one operation. However, localUpdate doesn't support
 * multiple tasks. If you use this, to update separate packages or to execute
 * multiple tasks, you'll need to run multiple instances of Cronable.
 *
 * The localUpdate method accepts 5 parameters:
 * - The type of package to be updated ('CIDRAM' or 'phpMussel').
 * - The username of the front-end account to be used by Cronable for updating the package.
 * - The password of the front-end account to be used by Cronable for updating the package.
 * - The location of the package loader file.
 * - What to update (specify 'Signatures' to update just signatures; or anything else to update everything).
 *
 * Examples:
 */
$Cronable->localUpdate('CIDRAM', 'username', 'password', '/public_html/cidram/loader.php', 'Signatures');
$Cronable->localUpdate('phpMussel', 'username', 'password', '/public_html/phpmussel/loader.php', 'Everything');

/** ---------- Then finally: ---------- */

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
