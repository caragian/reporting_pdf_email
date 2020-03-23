
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

### 4. Customize Variables

In this step is necessary to customize the following variables:

- $mailTo --> destination mail address
      

      

      
