<?php

require_once('CRM/Report/Form.php');

/**
 * @file
 */

class CRM_Iats_Form_Report_DownloadJournal extends CRM_Report_Form {

    public function __construct() {
        parent::__construct();
        $this->download();
    }

    protected function download() {
        $csvFilePath = CRM_Utils_Log_RecurringPayment::getCSVFilePath();
        if (file_exists($csvFilePath)) {
            if(filesize($csvFilePath) == 0) {
                CRM_Core_Session::setStatus('No Entries Logged. Empty File', ts('Error'), 'error');
                CRM_Utils_System::redirect('/dms/iATSAdmin');
            }
            // Else force download the file
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($csvFilePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($csvFilePath));
            readfile($csvFilePath);
            exit;
        } else {
            CRM_Core_Session::setStatus('File Does Not Exist', ts('Error'), 'error');
            CRM_Utils_System::redirect('/dms/iATSAdmin');
        }
    }

}
