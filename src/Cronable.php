<?php
/**
 * Cronable v1.2.2 (last modified: 2022.02.12).
 * @link https://github.com/Maikuolan/Cronable
 *
 * Description: Cronable is a simple script that allows auto-updating CIDRAM
 * and phpMussel via cronjobs.
 *
 * CRONABLE COPYRIGHT 2017 and beyond by Caleb Mazalevskis (Maikuolan).
 *
 * License: MIT License
 * @see LICENSE.txt
 */

namespace Maikuolan\Cronable;

class Cronable
{
    /**
     * @var string Cronable user agent.
     */
    private $ScriptUA = 'Cronable v1.2.2';

    /**
     * @var int Default timeout.
     */
    private $Timeout = 12;

    /**
     * @var array Will be populated by tasks.
     */
    private $Tasks = [];

    /**
     * @var string Output we'll send upon completing tasks.
     */
    public $Output = '';

    /**
     * @var bool Determines whether to display debugging information when relevant.
     */
    public $Debugging = false;

    /**
     * Generate error logs when debugging is enabled.
     *
     * @param string $Identifier
     * @param string $Method
     * @param string $Task
     * @param string $Results
     * @return void
     */
    private function cronableError($Identifier, $Method, $Task, $Results = 'Results are empty')
    {
        $Data = sprintf("Debugging (%1\$s):\n- Method: `%2\$s`.\n- Task: `%3\$s`.\n- %4\$s.\n\n", $Identifier, $Method, $Task, $Results);
        $File = __DIR__ . '/error.log';
        if ($Handle = fopen($File, 'a')) {
            fwrite($Handle, $Data);
            fclose($Handle);
        }
        $this->Output .= $Data;
    }

    /**
     * Used to send cURL requests.
     *
     * @param string $URI       The resource to request.
     * @param array $Params     An optional associative array of key-value
     *                          pairs to to send along with the request.
     * @param string $Timeout   An optional timeout limit (defaults to 12
     *                          seconds).
     * @return string           The results of the request.
     */
    private function request($URI, $Params = '', $Timeout = '')
    {
        if (!$Timeout) {
            $Timeout = $this->Timeout;
        }

        /** Initialise the cURL session. */
        $Request = curl_init($URI);

        $LCURI = strtolower($URI);
        $SSL = (substr($LCURI, 0, 6) === 'https:');

        curl_setopt($Request, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($Request, CURLOPT_HEADER, false);
        if (empty($Params)) {
            curl_setopt($Request, CURLOPT_POST, false);
        } else {
            curl_setopt($Request, CURLOPT_POST, true);
            curl_setopt($Request, CURLOPT_POSTFIELDS, $Params);
        }
        if ($SSL) {
            curl_setopt($Request, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            curl_setopt($Request, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($Request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($Request, CURLOPT_MAXREDIRS, 1);
        curl_setopt($Request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Request, CURLOPT_TIMEOUT, $Timeout);
        curl_setopt($Request, CURLOPT_USERAGENT, $this->ScriptUA);

        /** Execute and get the response. */
        $Response = curl_exec($Request);

        /** Close the cURL session. */
        curl_close($Request);

        /** Return the results of the request. */
        return $Response;
    }

    /**
     * Update method.
     *
     * @param array $Arr        Instructions for the update method (e.g.,
     *                          package, location, etc).
     * @return mixed            False(bool) on failure; True(bool) when
     *                          already up-to-date; Update message (string)
     *                          on success.
     */
    private function update($Arr)
    {
        if (!empty($Arr['Package'])) {
            if ($Arr['Package'] === 'CIDRAM') {
                $Package = 'CIDRAM';
                $Query = '?cidram-page=updates';
                $FormTarget = 'cidram-form-target';
            } elseif ($Arr['Package'] === 'phpMussel') {
                $Package = 'phpMussel';
                $Query .= '?phpmussel-page=updates';
                $FormTarget = 'phpmussel-form-target';
            }
        }

        /** Guard. */
        if (empty($Package) || empty($Arr['Username']) || empty($Arr['Password']) || empty($Arr['Location'])) {
            return false;
        }

        $Location = $Arr['Location'] . $Query;
        $Arr = [
            'CronMode' => empty($Arr['Mode']) ? 'All' : $Arr['Mode'],
            $FormTarget => 'updates',
            'do' => 'get-list',
            'username' => $Arr['Username'],
            'password' => $Arr['Password'],
        ];
        $Request = $this->request($Location, http_build_query($Arr));
        if (substr($Request, 0, 1) === '{' && substr($Request, -1) === '}') {
            $Request = json_decode($Request, true, 3);
            if (!empty($Request['state_msg'])) {
                return $Request['state_msg'];
            }
            if ($Arr['CronMode'] === 'Signatures') {
                if (empty($Request['outdated_signature_files'])) {
                    return false;
                }
                $Arr['ID'] = $Request['outdated_signature_files'];
            } else {
                if (empty($Request['outdated'])) {
                    return false;
                }
                $Arr['ID'] = $Request['outdated'];
            }
            $Arr['do'] = 'update-component';
            $Request = $this->request($Location, http_build_query($Arr));
        } elseif (!empty($Request)) {
            return false;
        }
        if (substr($Request, 0, 1) == '{' && substr($Request, -1) == '}') {
            $Request = json_decode($Request, true, 3);
            if (empty($Request)) {
                return false;
            }
            if (!empty($Request['state_msg'])) {
                return $Request['state_msg'];
            }
        }
        return true;
    }

    /**
     * Build identifier.
     *
     * @param string $Package
     * @param string $Location
     * @return string
     */
    private function buildIdentifier($Package, $Location)
    {
        $Location = preg_replace('~^(?:https?\:\/\/)?(?:www\d{0,3}\.)?~i', '', $Location);
        return '[' . $Package . '@' . $Location . ']';
    }

    /**
     * Create task method.
     *
     * @param string $Package
     * @param string $Username
     * @param string $Password
     * @param string $Location
     * @param string $Mode What to update (specify 'Signatures' to update just signatures; anything else to update everything).
     * @return void
     */
    public function createTask($Package, $Username, $Password, $Location, $Mode = 'All')
    {
        $this->Tasks[] = [
            'Package' => $Package,
            'Username' => $Username,
            'Password' => $Password,
            'Location' => $Location,
            'Mode' => $Mode
        ];
    }

    /**
     * Execute all tasks.
     *
     * @return void
     */
    public function execute()
    {
        $this->Output .= $this->ScriptUA . "\nTime: " . date('r') . "\n\n===\n";
        $Tasks = $this->Tasks;
        foreach ($Tasks as $Task) {
            $Identifier = empty($Task['Location']) ? '[Unknown]' : $this->buildIdentifier($Task['Package'], $Task['Location']);
            $Results = $this->update($Task);
            if ($Results === true) {
                $this->Output .= 'Everything already up-to-date at ' . $Identifier . ". :-)\n\n";
            } elseif ($Results === false) {
                $this->Output .= 'An error occurred while attempting to update at ' . $Identifier . ". :-(\n\n";
                if ($this->Debugging) {
                    $this->cronableError($Identifier, 'execute()', $Task, 'Results === `false`');
                }
            } else {
                $this->Output .= 'Status for ' . $Identifier . " is as follows:\n" . $Results . "\n\n";
            }
        }
        $this->Output .= "===\n\nTime: " . date('r') . "\n\n\n";
    }

    /**
     * Update locally.
     *
     * @param string $Package
     * @param string $Username
     * @param string $Password
     * @param string $Location
     * @param string $UpdateAll What to update (specify 'Signatures' to update just signatures; anything else to update everything).
     * @return void
     */
    public function localUpdate($Package, $Username, $Password, $Location, $UpdateAll = 'All')
    {
        /** Let's fake it all. */
        $_POST['CronMode'] = $UpdateAll;
        $_POST['username'] = $Username;
        $_POST['password'] = $Password;
        $_SERVER['HTTP_USER_AGENT'] = $this->ScriptUA;
        if ($PackageKnown = ($Package === 'CIDRAM' || $Package === 'phpMussel')) {
            if ($Package === 'CIDRAM') {
                $_SERVER['QUERY_STRING'] = 'cidram-page=updates';
                $_POST['cidram-form-target'] = 'updates';
            } elseif ($Package === 'phpMussel') {
                $_SERVER['QUERY_STRING'] = 'phpmussel-page=updates';
                $_POST['phpmussel-form-target'] = 'updates';
            }
        }
        if (empty($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '::1';
        }

        $this->Output .= $this->ScriptUA . "\nTime: " . date('r') . "\n\n===\n";
        $Identifier = empty($Location) ? '[Unknown]' : '[' . $Package . '@' . $Location . ']';
        if (is_readable($Location) && $PackageKnown) {
            /** To prevent the HTML output that we'd normally see when accessing everything normally. */
            ob_start();

            /** Let's call the package. */
            require $Location;

            /** We're done here. Reenable output. */
            ob_end_clean();

            if (empty($Results)) {
                $this->Output .= 'An error occurred while attempting to update at ' . $Identifier . ". :-(\n\n";
                if ($this->Debugging) {
                    $this->cronableError($Identifier, 'localUpdate()', $Location);
                }
            } elseif (empty($Results['state_msg'])) {
                $this->Output .= 'Everything already up-to-date at ' . $Identifier . ". :-)\n\n";
            } else {
                $this->Output .= 'Status for ' . $Identifier . " is as follows:\n" . $Results['state_msg'] . "\n\n";
            }
        } else {
            $this->Output .= 'An error occurred while attempting to update at ' . $Identifier . ". :-(\n\n";
            if ($this->Debugging) {
                $this->cronableError($Identifier, 'localUpdate()', $Location, 'Package not known or location unreadable');
            }
        }
        $this->Output .= "===\n\nTime: " . date('r') . "\n\n\n";
    }
}
