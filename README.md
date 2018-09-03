[![PHP >= 5.4.0](https://img.shields.io/badge/PHP-%3E%3D%205.4.0-8892bf.svg)](https://maikuolan.github.io/Compatibility-Charts/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-brightgreen.svg)](http://makeapullrequest.com)

## Cronable.

Cronable is a simple script that allows auto-updating [![CIDRAM >= 1.2.0](https://img.shields.io/badge/CIDRAM-%3E%3D%201.2.0-ff8800.svg)](https://maikuolan.github.io/Compatibility-Charts/) and [![phpMussel >= 1.1.0](https://img.shields.io/badge/phpMussel-%3E%3D%201.1.0-ff8800.svg)](https://maikuolan.github.io/Compatibility-Charts/) via cronjobs.

---

### How to install:

You can download the file containing the class, [Cronable.php](src/Cronable.php), directly from this repository, or, if you'd prefer, you can install it using Composer:

`composer require maikuolan/cronable`

Cronable is a stand-alone class that has no dependencies other than PHP, the cURL extension of PHP, and something to trigger it at the desired interval (generally, a cron manager of some description), and so, downloading it is all there really is to "installing" it.

---

### How to use:

For auto-updating CIDRAM or phpMussel with Cronable, front-end management will need to be enabled. Create a new account from the accounts page for Cronable to use, and set the permissions for this new account to "Cronable" (the "Cronable" permissions type is intended only for Cronable, and shouldn't be used for anything else). Take note of the username and password that you choose for this new account, because you'll need it in a moment.

Next, check the [examples.php](examples.php) file, and using the instructions and examples given in the file, create your update tasks as per necessary.

Using your cron manager or other triggering mechanism, create a new cron task with the desired update interval (please don't choose an excessively short interval, as the inbound requests received by the servers containing the updates may perceive this as abuse, and you may blocked by them as a consequence; checking for updates once a day should be more than enough; most updates tend to be released once per week or once per month anyhow), pointing to the file containing your Cronable update tasks.

That's everything. :-)

---

### Other information:

#### Licensing:
[MIT License](https://github.com/Maikuolan/Cronable/blob/master/LICENSE.txt).

#### For support:
Please use the issues page of this repository.

---

*Last modified: 3 September 2018 (2018.09.03).*
