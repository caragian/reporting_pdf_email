<?php

namespace Icinga\Module\Slm\scripts;

require_once '/neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email/TCPDF/tcpdf.php';
use TCPDF;

class ExportPdfReport 
{


    private $username;

    private $password;
    
    private $costum;

    private $domain = 'localhost';
    
    //Specify the email desination
    private $mailTo = 'nicolae.caragia@wuerth-phoenix.com';

    private $limit = 300;

    private $log = 0;

    private $path = '/tmp/reporting';

    private $message;

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

        $cliParams = getopt('u:p:d:P:H:c:');

        if (isset($cliParams['u'])) {
            $this->setUsername($cliParams['u']);
        }

        if (isset($cliParams['p'])) {
            $this->setPassword($cliParams['p']);
        }

        if (isset($cliParams['d'])) {
            $this->setDomain($cliParams['d']);
        }

        if (isset($cliParams['P'])) {
            $this->setPort($cliParams['P']);
        }

        if (isset($cliParams['H'])) {
            $this->setProtocol($cliParams['H']);
        }

        if (isset($cliParams['c'])) {
            $this->setCustom($cliParams['c']);
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

    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    public function setCustom($custom)
    {
        $this->custom = $custom;
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
        $this->message .= 'Usage: php /path-of-the-script/export_php_report.php [options] [-u] [-p]
        -u                  Admin username of the Neteye application.
        -d                  Domain name of the Neteye application. If the domain name is not passed then the script 
                            will use `localhost` as domain.
        -p                  Admin password of the Neteye application.
        -P                  Port.
        -H  <http|https>    Protocol.
        -c                  URL to report. Pay to attention , the url start from the word "neteye". Example = neteye/monitoring/list/services?host_state=2.
        -h, --help          To display the help section.' . chr(10);
    }


    //CURL REQUEST//
    protected function curlCall($requestUrl)
    {
        $content = '';
        $httpCode = '400';
        
        //Use $auth to declare the credetials without arguments
        //Comment $auth if you pass the credentials with arguments
        //$auth = 'user:secret';
        
        //Use Line $auth if you declare the crentials without arguments
        //if ($auth) {
 
        //Use this function if you pass the credentials with arguments
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
                        'Content-Type: application/json',
                        'Accept: application/json'
                    ],
                    CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                    CURLOPT_USERPWD => $this->getUsername() . ':' . $this->getPassword()
                    //CURLOPT_USERPWD => $auth
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

    //CONVERT RESULT JSON INTO HTML//
    public function jsonToHtml($data)
    {
        echo "\n\nConverting Json result from Html";
        /*Initializing temp variable to design table dynamically*/
        $temp = '<!DOCTYPE html>
		<head>
			<meta charset="UTF-8">
			<style>
				table {
					border-collapse: collapse;
				}
          
				table, td, th {
					border: 0.6px solid black;
				}
			</style>
		</head>
	<table  cellpadding="1" >
       
	 <thead>
		 <tr style="background-color:#524F4F;color:#ffffff;">
         		<td width="150"><b>Host Name</b></td>
          		<td width="250"><b>Host Output</b></td>
          		<td width="80"><b>Host State</b></td>
          		<td width="85"><b>In Downtime</b></td>
          		<td width="73"><b>Is Acknowledged</b></td>
         	</tr>';

        // /*Dynamically generating rows & columns*/
        for($i = 0; $i < sizeof($data); $i++)
        {
            //Service in Downtime
            if ($data[$i]['host_in_downtime'] == "0" ){
                $host_downtime = "No";
            } elseif ($data[$i]['host_in_downtime'] == "1") {
                $host_downtime = "Yes";
            };

            //Service Is Acknowledged
            if ($data[$i]['host_acknowledged'] == "0" ){
                $host_ack = "No";
            } elseif ($data[$i]['host_acknowledged'] == "1") {
                $host_ack = "Yes";
            };


            //Service State
            if ($data[$i]['host_state'] == "0"){
                $host_state = "UP";
                $style = '<td width="80" style="background-color:#5db25d;color:#ffffff;">';
            } elseif ($data[$i]['host_state'] == "1") {
                $host_state = "DOWN";
                $style = '<td width="80" style="background-color:#ff3300;color:#ffffff;">';
            };

            $host_output = $data[$i]['host_output'];
            // $host_output = substr($data[$i]['host_output'],0,50).'....';

            // Encode HTML (Output Field)
            $host_output = htmlentities($host_output, ENT_QUOTES);   

            $temp .= '<tr>';
            $temp .= '<td width="150">' . $data[$i]['host_name'] . '</td>';
            $temp .= '<td width="250">' . $host_output . '</td>';
            $temp .= $style . $host_state . '</td>';
            $temp .= '<td width="85">' . $host_downtime . '</td>';
            $temp .= '<td width="73">' . $host_ack . '</td>';
            $temp .= '</tr>';
        }


        $temp .= '
        </thead>
        </table>';


        $myfile = fopen("newfile.html", "w") or die("Unable to open file!");
        fwrite($myfile, $temp);
        fclose($myfile);

        $this->log = $i;
        return($temp);

    }

    //CREATION PDF HTML2PDF//
    public function htmlToPdf($html)
    {
        $date = getdate();

        $today = $date['year'].$date['mon'].$date['mday'];
        $pdfName = "host_problem_$today.pdf";

        echo "\n\nConverting Html to PDF";
 
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator("NetEye");
        $pdf->SetAuthor('NetEye4');
        $pdf->SetTitle('NeteEye4 Reporting Host Problem');

        // set default header data
        $pdf->SetHeaderData("logo.png", PDF_HEADER_LOGO_WIDTH, "NetEye", "Reporting Host Problem");

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('helvetica', 'B', 6);

        // add a page
        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, false, false, '');

     
                
        //Ausgabe der PDF
         
        $pdf->Output('/tmp/reporting/'.$pdfName, 'F');

    }


    //SEND PDF VIA MAIL//
    protected function sendMail(
        $mailTo,
        $file,
        $fromName = "Neteye4 Reporting",
        $from = "mail@domain.com",
        $message = "NetEye4 Monitoring Status Email",
        $subject    = "NetEye4 Monitoring Status Email"
        
    )
    {
        echo("\n\nSending Email to $mailTo");
        

        //header for sender info
        $headers = "From: $fromName"." <".$from.">";


        //boundary 
        $semi_rand = md5(time()); 
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

        $htmlContent = '<h1>NetEye Reporting Host Problem</h1><p>In this Report are reported '.  $this->log .' elements.</p><p>Limit of elements in the query is '. $this->limit .'.';

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

        echo("\n\nEmail sended successfully\n\n");

        return mail($mailTo, $subject, $message, $headers, $returnpath); 
    }


    //START//
    public function reportingApiCall()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, 0775, true);
        };

        
        
        try {
            if (!$this->isHelpOption()) {

                if($this->custom==NULL){
                    echo ("\n WARNING!!  DEFINE URL !!WARNING\n\n");
                    exit();
                }
                    $baseUrl = sprintf(
                        '%s://%s%s/'. $this->custom  . '&limit='.$this->limit,
                        $this->getProtocol(),
                        $this->getDomain(),
                        $this->getPort()
                    );
                    $response = $this->curlCall($baseUrl);

                    echo "\nBase URL: $baseUrl";


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

                    if ($this->status) {
                        
                        //Decode the JSON and convert it into an associative array.
                        $jsonDecoded = json_decode($response['content'], true);

                        $json_string = json_encode(json_decode($response['content']), JSON_PRETTY_PRINT);
                        //echo $json_string;
                        //exit();

                        //Run Json2Html and Html2Pdf
                        $this->htmlToPdf($this->jsonToHtml($jsonDecoded));

                        //Get File Name
                        $date = getdate();
                        $today = $date['year'].$date['mon'].$date['mday'];
                        $file = "/tmp/reporting/host_problem_$today.pdf";

                        //Send Email
                        $email = $this->sendMail($this->mailTo, $file);
                    
                    }
            
            } else {
                $this->helpSection();
            }
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
        }
        return $this->message;
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
