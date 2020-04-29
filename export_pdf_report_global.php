<?php

namespace Icinga\Module\Slm\scripts;

require_once '/neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email/TCPDF/tcpdf.php';
use TCPDF;

class ExportPdfReport 
{

    private $global_html;

    private $name;

    private $prefix_url = 'neteye/monitoring/list/';

    //Elements to Report
    private $array_url = array(
    array(
        'url'=>'services?service_state=2',
        'type'=>'service'
    ),
    array(
        'url'=>'hosts?host_state=1',
        'type'=>'host'
    ),
    array(
        'url'=>'services?service_state=1',
        'type'=>'service'
    ),
    array(
        'url'=>'hosts?host_state=0',
        'type'=>'host'
    ),
);

    private $username;

    private $password;
    
    private $costum;

    private $domain = 'localhost';
    
    //Specify the email desination
    private $mailTo = 'nicolae.caragia@wuerth-phoenix.com';

    private $limit = 100;

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

        $cliParams = getopt('u:p:d:P:H:n:');

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
        
        if (isset($cliParams['n'])) {
            $this->setName($cliParams['n']);
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

    //
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
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


    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    $name = $this->getName();

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
        -n                  Name Report
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

    // Header HTML
    public function write_header()
    {
        $temp = &$this->global_html;

        $temp .= '<style>
        table {
            border-collapse: collapse;
          }
          
        table, td, th {
            border: 0.6px solid black;
          }
        .host_up {
            background-color: #5db25d;
            color:#ffffff;
        }
        .host_down {
            background-color: #ff3300;
            color:#ffffff;
        }
        </style>';

        return($this->global_html);
    }

    //CONVERT RESULT JSON SERVICE INTO HTML//
    public function jsonToHtml_Service($data, $filter)
    {
        $filter = htmlentities($filter, ENT_QUOTES);
        $temp = &$this->global_html;
        echo "\n\nConverting Json result from Html";
        
        $temp .= '<table  cellpadding="1" >
        <thead>
         <tr style="background-color:#524F4F;color:#ffffff;">
          <td width="946" align="center"><b>Service Report Filter = '. $filter;
        $temp .= '</b></td></tr>
        <tr style="background-color:#524F4F;color:#ffffff;">
          <td width="150" align="center"><b>Host Name</b></td>
          <td width="130" align="center"><b>Service Description</b></td>
          <td width="490" align="center"><b>Service Output</b></td>
          <td width="60" align="center"> <b>Service State</b></td>
          <td width="46" align="center"><b>In Downtime</b></td>
          <td width="70" align="center"><b>Is Acknowledged</b></td>
         </tr>';

        // /*Dynamically generating rows & columns*/
        for($i = 0; $i < sizeof($data); $i++)
        {
            //Service in Downtime
            if ($data[$i]['service_in_downtime'] == "0" ){
                $service_downtime = "No";
            } elseif ($data[$i]['service_in_downtime'] == "1") {
                $service_downtime = "Yes";
            };

            //Service Is Acknowledged
            if ($data[$i]['service_acknowledged'] == "0" ){
                $service_ack = "No";
            } elseif ($data[$i]['service_acknowledged'] == "1") {
                $service_ack = "Yes";
            };

            //Service State
            if ($data[$i]['service_state'] == "0"){
                $service_state = "Ok";
                $style = '<td width="60" style="background-color:#5db25d;color:#ffffff;">';
            } elseif ($data[$i]['service_state'] == "1") {
                $service_state = "Warning";
                $style = '<td width="60" style="background-color:#fa4;;color:#ffffff;">';
            } elseif ($data[$i]['service_state'] == "2") {
                $service_state = "Critical";
                $style = '<td width="60" style="background-color:#ff3300;color:#ffffff;">';
            } elseif ($data[$i]['service_state'] == "3") {
                $style = '<td width="60" style="background-color:#c7f;color:#ffffff;">';
                $service_state = "Unknown";
            };

            //Service Output
            //$service_output = substr($data[$i]['service_output'],0,50).'....';

            $service_output = $data[$i]['service_output'];

            $service_output = htmlentities($service_output, ENT_QUOTES);
            
	        $temp .= '<tr>';
            $temp .= '<td width="150">' . $data[$i]['host_name'] . '</td>';
            $temp .= '<td width="130">' . $data[$i]['service_description'] . '</td>';
            $temp .= '<td width="490">' . $service_output . '</td>';
            $temp .= $style . $service_state . '</td>';
            $temp .= '<td width="46">' . $service_downtime . '</td>';
            $temp .= '<td width="70">' . $service_ack . '</td>';
            $temp .= '</tr>';
        }


        $temp .= '
        </thead>
        </table><br><br>';

       

        $this->log = $this->log + $i;

    }

    // CONVERTING JSON HOST TO HTML
    public function jsonToHtml_Host($data,$filter)
    {
        $filter = htmlentities($filter, ENT_QUOTES);
        $temp = &$this->global_html;
        echo "\n\nConverting Json result from Html";

        
        
        $temp .= '
	<table  cellpadding="1" >
       
	 <thead>
         <tr style="background-color:#524F4F;color:#ffffff;">
                <td width="946" align="center"><b>Host Report Filter = '.$filter;
        $temp .= '</b></td></tr>
        <tr style="background-color:#524F4F;color:#ffffff;">
         		<td width="216"><b>Host Name</b></td>
          		<td width="480"><b>Host Output</b></td>
          		<td width="80"><b>Host State</b></td>
          		<td width="85"><b>In Downtime</b></td>
          		<td width="85"><b>Is Acknowledged</b></td>
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
                $style = '<td width="80" class="host_up">';
            } elseif ($data[$i]['host_state'] == "1") {
                $host_state = "DOWN";
                $style = '<td width="80" class="host_down">';
            };

            $host_output = $data[$i]['host_output'];
            // $host_output = substr($data[$i]['host_output'],0,50).'....';

            // Encode HTML (Output Field)
            $host_output = htmlentities($host_output, ENT_QUOTES);   

            $temp .= '<tr>';
            $temp .= '<td width="216">' . $data[$i]['host_name'] . '</td>';
            $temp .= '<td width="480">' . $host_output . '</td>';
            $temp .= $style . $host_state . '</td>';
            $temp .= '<td width="85">' . $host_downtime . '</td>';
            $temp .= '<td width="85">' . $host_ack . '</td>';
            $temp .= '</tr>';
        }


        $temp .= '
        </thead>
        </table><br><br>';


        

        $this->log = $this->log + $i;

    }

    //CREATION PDF HTML2PDF//
    public function htmlToPdf($html)
    {
        $date = getdate();

        $today = $date['year'].$date['mon'].$date['mday'];
        $pdfName = $name."_$today.pdf";

        $myfile = fopen("global.html", "w") or die("Unable to open file!");
        fwrite($myfile, $html);
        fclose($myfile);

        echo "\n\nConverting Html to PDF";
 
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator("NetEye");
        $pdf->SetAuthor('NetEye4');
        $pdf->SetTitle('NeteEye4 Reporting Global Problem');

        // set default header data
        $pdf->SetHeaderData("logo.png", PDF_HEADER_LOGO_WIDTH, "NetEye", "Reporting Global Problem");

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
        $pdf->SetFont('helvetica', 'B', 7);

        // add a page
        $pdf->AddPage('L');


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
        $name = $this->getName();
        echo("\n\nSending Email to $mailTo");
        

        //header for sender info
        $headers = "From: $fromName"." <".$from.">";


        //boundary 
        $semi_rand = md5(time()); 
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

        $htmlContent = '<h1>NetEye Reporting Global '.$name.'</h1><p>In this Report are reported '.  $this->log .' elements.</p><p>Limit of elements in the query is '. $this->limit .'.';

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

        $type_service = 'service';
        $type_host = 'host';

        
        try {
            if (!$this->isHelpOption()) {

                $this->write_header();
                foreach ($this->array_url as $url) {
                    if($url['url']==NULL){
                        echo ("\n WARNING!!  DEFINE URL !!WARNING\n\n");
                        exit();
                    }
                    
                        $baseUrl = sprintf(
                            '%s://%s%s/'.$this->prefix_url.$url['url']  . '&limit='.$this->limit,
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
                            
                            if ($url['type'] == $type_service){
                                //Decode the JSON and convert it into an associative array.
                                $jsonDecoded_Service = json_decode($response['content'], true);
    
                                // $json_string = json_encode(json_decode($response['content']), JSON_PRETTY_PRINT);
                                // echo $json_string;
                                // exit();
        
                                //Run Json2Html and Html2Pdf
                                $filter = $url['url'];
                                $this->jsonToHtml_Service($jsonDecoded_Service, $filter);

                                
                            } elseif ($url['type'] == $type_host) {
                                //Decode the JSON and convert it into an associative array.
                                $jsonDecoded_Host = json_decode($response['content'], true);
    
                                // $json_string = json_encode(json_decode($response['content']), JSON_PRETTY_PRINT);
                                // echo $json_string;
                                // exit();
        
                                //Run Json2Html and Html2Pdf
                                $filter = $url['url'];
                                $this->jsonToHtml_Host($jsonDecoded_Host, $filter);
                            };
    
                            $name = $this->getName();
                            $this->htmlToPdf($this->global_html);
                            //Get File Name
                            $date = getdate();
                            $today = $date['year'].$date['mon'].$date['mday'];
                            $file = "/tmp/reporting/$name" . "_". "$today.pdf";
                           
                        
                        }

                         
                };
                //Send Email
                $email = $this->sendMail($this->mailTo, $file, $name);

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
