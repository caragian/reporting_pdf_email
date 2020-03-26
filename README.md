
# NetEye 4 Reporting PDF via Email

This script allows you to send a Email with the report of your Service Problem in NetEye4.

### 1. Clone Git directory

      git clone https://github.com/caragian/reporting_pdf_email.git
      git clone https://github.com/tecnickcom/TCPDF.git
     
### 2. Create necessary directory for the script
      
Directory for Script

      mkdir /neteye/shared/icingaweb2/extras/reporting
      mkdir /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      
### 3. Copy reporting_pdf_email.git content in /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email

      cp /root/git-rep/report_pdf_email/* /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      cp /root/git-rep/TCPDF /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      
Move the NetEye Logo in directory /TCPDF/examples/images/
      
      mv logo.png /TCPDF/examples/images/

### 4. Customize variables

In this step is necessary to customize the following variable:

      $mailTo = 'mail@domain.com'  -->  destination mail address
      $limit = 300 --> max elements in report ( It's advisable leave it with this value )
      $path = '/tmp/reporting' --> path which contains pdf reporting

In order to customize also the received email you can customize also this optional variables:

      $fromName = "Neteye4 Reporting",
      $from = "user@domain.com",
      $message = "NetEye4 Monitoring Status Email",
      $subject    = "NetEye4 Monitoring Status Email"
      
### 5. Run Script

Run Script with argument

      php export_pdf_report.php -root -p secret
      
You can also run the script without arguments , but **warning**, you have to pay attention at the section of credentials valdiation in **protected function curlCall($requestUrl)**.


## Optional
In order to automate this script you can create a CronJob.

      crontab -e
      
    * * * * *  systemctl status httpd.service > /dev/null 2>&1 &&  /usr/bin/php /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email/export_pdf_report.php -u root -p secret  >/dev/null


      

      
