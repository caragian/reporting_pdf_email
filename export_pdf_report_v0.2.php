<?php

namespace Icinga\Module\Slm\scripts;

class ExportPdfReport
{
    private $username;

    private $password;

    private $domain = 'localhost';

    private $message;

    private $mailTo = 'Nicolae.Caragia@wuerth-phoenix.com';

    private $port = '';

    private $protocol = 'https';

    private $status = true;

    private $helpOption = false;

    public function __construct()
    {
        if (isset($_SERVER['argv'])) {
            if (in_array('-h', $_SERVER['argv']) || in_array('--help', $_SERVER['argv'])) {
                $this->setHelpOption(true);
            }
        }

        $cliParams = getopt('u:p:');

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
                        // 'Connection: keep-alive',
                        // 'Cookie: icingaweb2-tzo=3600-0; icingaweb2-session=1584611259; Icingaweb2=3dg9ktg00f22fgjgkdkaqnak6f',
                        // 'Upgrade-Insecure-Requests: 1',
                        // 'Cache-Control: max-age=0'

                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                        'Accept-Encoding: gzip, deflate, br',
                        'Connection: keep-alive',
                        // 'Cookie: icingaweb2-tzo=3600-0; icingaweb2-session=1584615767; Icingaweb2=3dg9ktg00f22fgjgkdkaqnak6f',
                        'Upgrade-Insecure-Requests: 1',
                        'Cache-Control: max-age=0'
                    ],
                    CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                    CURLOPT_USERPWD => $this->getUsername() . ':' . $this->getPassword(),
                    CURLOPT_REFERER => 'https://localhost/neteye/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=pdf'
                ]
            );

            
            // obtain result
            $content = curl_exec($ch);
            
            // $httpCode = $info['http_code'];
            $error = curl_error($ch);

            $curl_info = curl_getinfo($ch);
            $curl_cookie = curl_getinfo($ch, CURLINFO_COOKIELIST);
            print_r($curl_cookie);
            // print_r($curl_info);

            $user = $this->getUsername();
            $password = $this->getPassword();
            echo "$user\n";
            echo "$password\n";

            $info =  phpinfo();
            // print_r($info);
            // exit;



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
        $fromName = "Neteye4 - Service Problem",
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

        $date = getdate();

        $htmlContent = '<h1>NetEye Reporting Service Problem</h1>';

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
        echo ("\nSending E-Mail to $mailTo\n");
        return mail($mailTo, $subject, $message, $headers, $returnpath); 
    }
    
    public function reportingApiCall()
    {
            if (!$this->isHelpOption()) {
                    $baseUrl = sprintf(
                        '%s://%s%s/neteye/monitoring/list/services?service_problem=1&sort=service_severity&dir=desc&format=pdf',
                        $this->getProtocol(),
                        $this->getDomain(),
                        $this->getPort()
                         
                    );
                  
                    $response = $this->curlCall($baseUrl);

                    echo("\nBaseURL: $baseUrl\n");

                    // print_r($response);

                    if (!strlen($response['content']) || $response['http_code'] == 0) {
                        $this->message = 'Got error response (Code: ' . $response['http_code'] . ')' . chr(10);
                        $this->status = false;
                    }
                    
                    

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

                    $date = getdate();
                    $today = $date['year'].$date['mon'].$date['mday'];
                    $fileName = "/tmp/exportPdf/service_problem_$today.pdf";

                    
                    if (file_exists($fileName)) {
                        echo ("\nThe file $fileName exists.\nOnly Email will be sent.\n");
                    } else {
                        echo "\nThe file $fileName does not exist\nCreation in progress...\n";
                        $myFile = fopen($fileName, "w") or die("Unable to open file!");
                        fwrite($myFile, $response['content']);
                        fclose($myFile);
                        if (file_exists($fileName)) {
                            echo ("$fileName creation is completed\n");
                        }
                        else{
                            echo ("Something went wrong!!\n");
                        }
                    }

                    $file = $fileName;
                    
                    
                    $email = $this->sendMail($this->mailTo, $file);
                    exit;

                }else {
                    $this->helpSection();
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
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }
}





$api = new ExportPdfReport();
echo $api->reportingApiCall();
