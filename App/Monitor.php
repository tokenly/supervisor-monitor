<?php

namespace App;

use App\Log;
use Supervisor\Connector\XmlRpc;
use Supervisor\Supervisor;
use Tokenly\ConsulHealthDaemon\ConsulClient;
use fXmlRpc\Client;
use Exception;

/**
* Monitor
*/
class Monitor
{

    static $INSTANCE;

    protected $supervisor = null;

    public static function instance() {
        if (self::$INSTANCE === null) {
            self::$INSTANCE = new Monitor();
        }
        return self::$INSTANCE;
    }


    public function sendStatusUpdatesToConsul(ConsulClient $consul) {
        $processes = $this->supervisor->getAllProcessInfo();
        // echo "\$processes: ".json_encode($processes, 192)."\n";

        $any_errors = [];
        foreach($processes as $process) {
            try {
                $id = getenv('APP_PREFIX').'supervisor_'.$process['name'];
                $is_up = ($process['statename'] == 'RUNNING');
                if ($is_up) {
                    Log::debug("process $id was UP");
                    $consul->checkPass($id);

                } else {
                    $note = "Process was in state {$process['statename']}";
                    Log::debug("process $id was DOWN. {$note}");
                    $consul->checkFail($id, $note);
                }
            } catch (Exception $e) {
                echo "ERROR: ".$e->getMessage()."\n";
                Log::warn("ERROR: ".$e->getMessage());
                $any_errors[] = $e->getMessage();
            }
        }

        if ($any_errors) {
            throw new Exception(implode("\n", $any_errors), 1);
        }
    }

    protected function __construct() {
        $url = getenv('MONITOR_URL');
        if (!strlen($url)) { $url = 'http://127.0.0.1:9001/RPC2'; }
        $xml_rpc_client = new Client($url);
        $connector = new XmlRpc($xml_rpc_client);
        $this->supervisor = new Supervisor($connector);


    }

}
