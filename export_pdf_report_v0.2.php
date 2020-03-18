<?php

namespace Icinga\Module\Slm\scripts;

class ExportCsvReport
{
    private $username;

    private $password;

    private $domain = 'localhost';

    // private $reportId = 0;

    private $message;

    private $mailTo = 'Nicolae.Caragia@wuerth-phoenix.com';

    private $port = '';

    private $protocol = 'https';

    private $filePath = '';

    private $status = true;

    private $helpOption = false;

    private $forceOverwrite = false;

    public function __construct()
    {
        if (isset($_SERVER['argv'])) {
            if (in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) {
                $this->setHelpOption(true);
            } elseif (in_array('-F', $_SERVER['argv'])) {
                $this->setForceOverwrite(true);
            }
        }

        $cliParams = getopt('u:p:d:i:f:P:H:');

        if (isset($cliParams['u'])) {
            $this->setUsername($cliParams['u']);
        }

        if (isset($cliParams['p'])) {
            $this->setPassword($cliParams['p']);
        }
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPort($port)
    {
        $this->port = ':' . $port;
    }

    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return mixed
     */

    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    protected function helpSection()
    {
        // This function will print the help section
        $this->message = 'This PHP curl based script is used to generate the reporting data into PDF file.' . chr(10);
        $this->message .= 'Usage: php /path-of-the-script/export_pdf_report.php [options] [-u] [-p]
        -u                  Admin username of the Neteye application.=
        -p                  Admin password of the Neteye application.
        >  <path>                 Destination File Path
        -h, --help          To display the help section.' . chr(10);
    }

    protected function curlCall($requestUrl)
    {
        $content = '';
        $httpCode = '400';
        if (strlen($this->getUsername()) && strlen($this->getPassword())) {
            // Initiate curl
            $ch = curl_init();
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_URL => $requestUrl,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => [
                        // 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0',
                        // 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        // 'Accept-Language: en-US,en;q=0.5',
                        // 'Accept-Encoding: gzip, deflate, br',
                        // 'Referer: https://localhost/neteye/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=pdf',
                        // 'Connection: keep-alive',
                        // 'Upgrade-Insecure-Requests: 1',
                        // 'Cache-Control: max-age=0'


                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                        'Accept-Encoding: gzip, deflate, br',
                        'Connection: keep-alive',
                        'Referer: https://localhost/neteye/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=pdf',
                        'Cookie: Icingaweb2=mvhimo86mslpt5dd99044vsnna; icingaweb2-tzo=3600-0; icingaweb2-session=1584375558',
                        'Upgrade-Insecure-Requests: 1',
                        'Cache-Control: max-age=0'
                        
                    ],
                    CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                    CURLOPT_USERPWD => $this->getUsername() . ':' . $this->getPassword()
                ]
            );

            // obtain result
            $content = curl_exec($ch);
            $info = curl_getinfo($ch);
            $httpCode = $info['http_code'];
            $error = curl_error($ch);

            // Close curl connection
            curl_close($ch);
        } else {
            $error = 'Invalid username or password' . chr(10);
        }

        return [
            'content' => $content,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }


    protected function sendMail(
        $mailTo,
        $file,
        $fromName = "Nicolae",
        $from = "pbzneteye4@wuerth-phoenix.com",
        $message = "NetEye4 Monitoring Status Email",
        $subject    = "NetEye4 Monitoring Status Email"
        
    )
    {


        
        //header for sender info
        $headers = "From: $fromName"." <".$from.">";


        //boundary 
        $semi_rand = md5(time()); 
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

        $htmlContent = '<h1>PHP Email with Attachment by CodexWorld</h1>
            <p>This email has sent from PHP script with attachment.</p>';

        //headers for attachment 
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 

        //multipart boundary 
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
        "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n"; 

        //preparing attachment
        if(!empty($file) > 0){
            if(is_file($file)){
                $message .= "--{$mime_boundary}\n";
                $fp =    @fopen($file,"rb");
                $data =  @fread($fp,filesize($file));

                @fclose($fp);
                $data = chunk_split(base64_encode($data));
                $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" . 
                "Content-Description: ".basename($file)."\n" .
                "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" . 
                "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
            }
        }
        $message .= "--{$mime_boundary}--";
        $returnpath = "-f" . $from;

        return mail($mailTo, $subject, $message, $headers, $returnpath); 
    }
    
    public function reportingApiCall()
    {
                if (True){
                    $baseUrl = sprintf(
                        '%s://%s%s/neteye/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=pdf',
                        'https',
                        'localhost',
                        ''
                        // $this->getProtocol(),
                        // $this->getDomain()
                        // $this->getPort()
                    );
                    $response = $this->curlCall($baseUrl);



                $cookie = session_get_cookie_params();

                print_r($cookie);
                exit();

                    
                    

                    if (!strlen($response['content']) || $response['http_code'] == 0) {
                        $this->message = 'Got error response (Code: ' . $response['http_code'] . ')' . chr(10);
                        $this->status = false;
                    }
                    
                    $date = getdate();
                    $today = $date['year'].$date['mon'].$date['mday'];
                    $fileName = "/tmp/exportPdf/service_problem_$today.pdf";
                    $myFile = fopen($fileName, "w") or die("Unable to open file!");
                    fwrite($myFile, $response['content']);
                    fclose($myFile);

                    $file = $fileName;

                    if (!isset($response['http_code']) ||
                        $response['http_code'] < 200 ||
                        $response['http_code'] >= 300
                    ) {
                        $errorMsg = '';
                        if (!strlen($response['content'])) {
                            $errorMsg = ':' . $response['content'];
                        } elseif (!strlen($response['error'])) {
                            $errorMsg = ':' . $response['error'];
                        }
                        $this->message = sprintf(
                            'Something went wrong!! Error (%s) %s' . chr(10),
                            $response['http_code'],
                            $errorMsg
                        );
                        $this->status = false;
                    }
                    

                    $email = $this->sendMail($this->mailTo, $file);
                    exit;

                    if ($this->status) {
                        $content =  json_decode(
                            $response ['content'],
                            true
                        );
                        $folder = dirname($this->getFilePath());
                        if (!file_exists($folder)) {
                            $this->message = 'Invalid file path' . chr(10);
                        } else {
                            $fileAlreadyExists = file_exists($this->getFilePath());
                            if (!$fileAlreadyExists || $this->isForceOverwrite()) {
                                //Give our CSV file a name.
                                if ($this->isForceOverwrite() && $fileAlreadyExists) {
                                    unlink($this->getFilePath());
                                }
                                if ($this->jsonToCSV($content, $this->getFilePath())) {
                                    $this->message = 'Completed!!' . chr(10);
                                }
                            } else {
                                // output an error
                                $this->message = 'File is already existing' . chr(10);
                            }
                        }
                    }
                }
            }




    /**
     * @return bool
     */
    public function isHelpOption()
    {
        return $this->helpOption;
    }

    /**
     * @param bool $helpOption
     */
    public function setHelpOption($helpOption)
    {
        $this->helpOption = $helpOption;
    }

    /**
     * @return bool
     */
    public function isForceOverwrite()
    {
        return $this->forceOverwrite;
    }

    /**
     * @param bool $forceOverwrite
     */
    public function setForceOverwrite($forceOverwrite)
    {
        $this->forceOverwrite = $forceOverwrite;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }
}





$api = new ExportCsvReport();
echo $api->reportingApiCall();
