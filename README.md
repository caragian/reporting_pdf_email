
# NetEye 4 Reporting PDF via Email

This script allows you to send a Email with the report of your Service Problem in NetEye4.

### 1. Clone Git directory

      git clone https://github.com/caragian/reporting_pdf_email.git
      git clone https://github.com/tecnickcom/TCPDF.git
     
### 2. Create necessary directory for the script

Directory for pdf files

      mkdir /tmp/reporting
      
Directory for Script

      mkdir /neteye/shared/icingaweb2/extras/reporting
      mkdir /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      
### 3. Copy reporting_pdf_email.git content in /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email

      cp /root/git-rep/report_pdf_email/* /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      cp /root/git-rep/TCPDF /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email
      
Move the NetEye Logo in direcotory /TCPDF/examples/images/

### 4. Customize variables

In this step is necessary to customize the following variable:

      $mailTo = 'mail@domain.com'  -->  destination mail address

In order to customize also the received email you can customize also this optional variables:

      $fromName = "Neteye4 Reporting",
      $from = "user@domain.com",
      $message = "NetEye4 Monitoring Status Email",
      $subject    = "NetEye4 Monitoring Status Email"
      
### 5. Run Script

Run Script with argument

      php export_pdf_report.php -root -p $(cat /root/.pwd_icingaweb2_root)
      
You can also run the script without arguments , but **warning**, you have to pay attention at the section of credentials valdiation in **protected function curlCall($requestUrl)**.


## Optional
In order to automate this script you can create a CronJob.

      crontab -e
      
      * * * * *  /usr/bin/php /neteye/shared/icingaweb2/extras/reporting/reporting_pdf_email/export_pdf_report.php -u root -p $(cat .pwd_icingaweb2_root >/dev/null

      

      
