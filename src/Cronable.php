<?php
namespace Maikuolan\Cronable;

/**
 * Cronable v1.0.0 (last modified: 2017.10.29).
 *
 * Description: Cronable is a simple script that allows auto-updating CIDRAM
 * and phpMussel via cronjobs.
 *
 * CRONABLE COPYRIGHT 2017 and beyond by Caleb Mazalevskis (Maikuolan).
 *
 * License: MIT License
 *
 * @see LICENSE.txt
 */
class Cronable
{

    /** Cronable user agent. */
    private $ScriptUA = 'Cronable v1.0.0';

    /** Default timeout. */
    private $Timeout = 12;

    /** Will be populated by tasks. */
    private $Tasks = [];

    /** Output we'll send upon completing tasks. */
    public $Output = '';

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
    
    /** Update method. */
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
        if (empty($Package) || empty($Arr['Username']) || empty($Arr['Password']) || empty($Arr['Location'])) {
            return false;
        }
        $Location = $Arr['Location'] . $Query;
        $Arr = [
            'CronMode' => 1,
            $FormTarget => 'updates',
            'do' => 'get-list',
            'username' => $Arr['Username'],
            'password' => $Arr['Password'],
        ];
        $Request = $this->request($Location, http_build_query($Arr));
        if (substr($Request, 0, 1) == '{' && substr($Request, -1) == '}') {
            $Request = json_decode($Request, true, 3);
            if (empty($Request)) {
                return false;
            }
            if (!empty($Request['state_msg'])) {
                return $Request['state_msg'];
            }
            if (!empty($Request['outdated'])) {
                $Arr['ID'] = $Request['outdated'];
                $Arr['do'] = 'update-component';
                $Request = $this->request($Location, http_build_query($Arr));
            } else {
                return false;
            }
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
    
    /** Create task method. */
    public function createTask($Package, $Username, $Password, $Location)
    {
        $this->Tasks[] = ['Package' => $Package, 'Username' => $Username, 'Password' => $Password, 'Location' => $Location];
    }
    
    /** Execute all tasks. */
    public function execute()
    {
        $this->Output .= $this->ScriptUA . "\nTime: " . date('r') . "\n\n===\n";
        $Tasks = $this->Tasks;
        foreach ($Tasks as $Task) {
            $Identifier = empty($Task['Location']) ? '[Unknown]' : $Task['Location'];
            $Results = $this->update($Task);
            if ($Results === true) {
                $this->Output .= 'Everything already up-to-date at "' . $Identifier . "\". :-)\n\n===\n";
            } elseif ($Results === false) {
                $this->Output .= 'An error occurred while attempting to update at "' . $Identifier . "\". :-(\n\n===\n";
            } else {
                $this->Output .= 'Status for "' . $Identifier . " is as follows:\n" . $Results . "\n\n===\n";
            }
        }
        $this->Output .= "\nTime: " . date('r') . "\n\n\n";
    }

}
