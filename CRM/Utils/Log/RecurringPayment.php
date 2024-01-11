<?php

class CRM_Utils_Log_RecurringPayment {

    private $csvFilePath;

    public function __construct($csvFilePath) {
        $this->csvFilePath = $csvFilePath;
        if(filesize($csvFilePath) == 0) {
            $this->addHeader();
        }
    }

    public function addStatus($logData, $logMessage = ''): void {
        // Open the CSV file with new line to be appended at the bottom
        $file = fopen($this->csvFilePath, 'a');
        if ($file && !empty($logData)) {
            // add timestamp
            $logData['timeStamp'] = date('Y/m/d H:i:s');

            // Any log Message if present
            if(!empty($logMessage)) {
                $logData['message'] = $logMessage;
            }

            fputcsv($file, array_values($logData));
            fclose($file);
        }
    }

    private function addHeader(): void {
        $file = fopen($this->csvFilePath, 'a');
        $header = [
          'Contact ID',
          'Contribution ID',
          'Amount',
          'Contribution Recur ID',
          'Received Date',
          'Transaction ID',
          'Invoice ID',
          'Payment Processor',
          'iATS Response',
          'Status',
          'Log Timestamp',
          'Log Message',
        ];
        fputcsv($file, $header);
        fclose($file);
        return;
    }

    public static function getCSVFilePath(string $fileName = "iatslog"): string {
        $baseDir = CRM_Core_Config::singleton()->uploadDir.'iats/log/';
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }
        $csvFilePath = $baseDir.$fileName.'.csv';
        return $csvFilePath;
    }
}
?>