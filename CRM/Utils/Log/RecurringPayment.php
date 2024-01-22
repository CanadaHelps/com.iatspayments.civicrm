<?php

class CRM_Utils_Log_RecurringPayment {

    private $fileType;
    private $csvFilePath;

    public function __construct($fileType = '') {
        $this->fileType = $fileType;
        $csvFilePath = $this->getCSVFilePath($fileType);
        $this->csvFilePath = $csvFilePath;
        if(filesize($csvFilePath) == 0) {
            $this->addHeader();
        }
    }

    public function addLog($logData, $logMessage = ''): void {
        // Open the CSV file with new line to be appended at the bottom
        $file = fopen($this->csvFilePath, 'a');
        if ($file && !empty($logData)) {
            // add timestamp
            $logData['timeStamp'] = date('Y/m/d H:i:s');

            // Any log Message if present
            if(!empty($logMessage)) {
                if(is_array($logMessage)) {
                    $logData['message'] = json_encode($logMessage);
                } else {
                    $logData['message'] = $logMessage;
                }
            }

            fputcsv($file, array_values($logData));
            fclose($file);
        }
    }

    private function addHeader(): void {
        $file = fopen($this->csvFilePath, 'a');
        $defaultHeader = [
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

        $devHeader = [
            'Invoice Num',
            'Amount',
            'Payment Method',
            'Request Data',
            'Contribution ID',
            'Status',
            'Status Code',
            'Remote ID',
            'Is Recurring',
            'TimeStamp',
            'Log Message',
        ];
        if($this->fileType == 'dev') {
            fputcsv($file, $devHeader);
        } else {
            fputcsv($file, $defaultHeader);
        }
        fclose($file);
        return;
    }

    public static function getCSVFilePath(string $fileType = '', string $fileName = "iatslog"): string {
        $baseDir = CRM_Core_Config::singleton()->uploadDir.'iats/log/';
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }
        $csvFilePath = $baseDir.$fileName.'.csv';
        if($fileType == 'dev') {
            $csvFilePath = $baseDir.'iats-log-dev.csv';
        }
        return $csvFilePath;
    }
}
?>