<?php
/**
 * Curl base file download with chunking
 */
    class downloader
    {
        private $url;
        private $length = 37500000;
        private $pos = 0;
        private $timeout = 60;

        public function __construct($url, $length)
        {
            $this->url = $url;

            $this->setLength($length);
        }

        public function setLength($length)
        {
            $this->length = $length;
        }

        public function setTimeout($timeout)
        {
            $this->timeout = $timeout;
        }

        public function setPos($pos)
        {
            $this->pos = $pos;
        }

        public function getPos()
        {
            return $this->pos;
        }

        public function start($local)
        {
            $part = $this->getPart("0-1");

            // Check partial Support
            if ($part && strlen($part) === 2) {
                // Split data with curl
                $this->runPartial($local);
            } else {
                // Use stream copy
                $this->runNormal($local);
            }
        }

        private function runNormal($local)
        {
            $in = fopen($this->url, "r");
            $out = fopen($local, 'w');
            $pos = ftell($in);
            while (($pos = ftell($in)) <= $this->pos) {
                $n = ($pos + $this->length) > $this->length ? $this->length : $this->pos;
                fread($in, $n);
            }
            $this->pos = stream_copy_to_stream($in, $out);
            return $this->pos;
        }

        private function runPartial($local)
        {
            $i = $this->pos;
            $fp = fopen($local, 'w');
            fseek($fp, $this->pos);
            while (true) {
                $data = $this->getPart(sprintf("%d-%d", $i, ($i + $this->length)));

                $i += strlen($data);
                fwrite($fp, $data);

                $this->pos = $i;
                if ($data === -1)
                    throw new Exception("File Corupted");

                if (!$data)
                    break;
            }

            fclose($fp);
        }

        private function getPart($range)
        {
            $param = $_REQUEST;
            //$oAuthToken = $param['oAuthToken'];
            //$authHeader = 'Authorization: Bearer ' . $oAuthToken;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RANGE, $range);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            //curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Request not Satisfiable
            if ($code == 416)
                return false;

            // Check 206 Partial Content
            if ($code != 206)
                return -1;

            return $result;
        }
    }
	
	
//uses
 
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '400000M'); //set memory limit in php.ini

$getUrl = 'http//domain.com/test.zip';
$file_size = 40000; //byte
$destination = '/var/www/html/where_to_be_save'; //where to be save in server

try {
	$download = new downloader($getUrl, $file_size);
	$download->start($destination); // Start Download Process
} catch (Exception $e) {
	printf("Copied %d bytes\n", $pos = $download->getPos());
}

