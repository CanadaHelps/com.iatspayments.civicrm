<?php

class CRM_Utils_Log_IatsPayment {

    private $fileType = '';
    private $csvFilePath = '';

    protected $_paymentMethod = NULL;

    public function __construct($fileType = '') {
        $this->fileType = $fileType;
        $csvFilePath = $this->getCSVFilePath($fileType);
        $this->csvFilePath = $csvFilePath;
        if(filesize($csvFilePath) == 0) {
            $this->addHeader();
        }
    }
    
    public function setPaymentMethod(string $paymentMethod): void {
        $this->_paymentMethod = $paymentMethod;
    }

    public function getPaymentMethod(): string {
        return $this->_paymentMethod;
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

    public static function removeConfidentialInfo(array $requestLog, $vault_id = FALSE): array {
        $confidentialData = ['cardNumber', 'cardExpYear', 'cardExpMonth', 'cVV', 'ownerCity', 'ownerState', 'ownerStreet'];
        foreach ($confidentialData as $confV) {
            if(isset($requestLog[$confV])) {
                unset($requestLog[$confV]);
            }
        }
        if($vault_id) {
            unset($requestLog['vaultKey']);
            unset($requestLog['vaultId']);
        }
        return $requestLog;
    }

    public function buildRequestLog($requestLog, $isRecurringJob = 0): array {
        $logData = [];
        $payment_method = $this->getPaymentMethod();
        if($isRecurringJob) {
            $logData = [
                'orderId' => $requestLog['invoice_id'],
                'amount' => $requestLog['total_amount'],
                'paymentMethod' => $payment_method,
                'requestData' => json_encode($requestLog),
            ];
        } else {
            if($payment_method == 'Credit Card (1st Pay)') {
                $logData = [
                    'orderId' => $requestLog['orderId'],
                    'amount' => $requestLog['transactionAmount'],
                    'paymentMethod' => $payment_method,
                    'requestData' => json_encode($requestLog),
                ];
            }
            if($payment_method == 'ACH_EFT') {
                $logData = [
                    'invoice' => $requestLog['invoiceNum'],
                    'amount' => $requestLog['total'],
                    'paymentMethod' => $payment_method,
                    'requestData' => json_encode($requestLog),
                ];
            }
        }
        return $logData;
    }

    public function buildACHRequestJournalLog($contribution, $journal_entry): array {
        $logData = [];
        $logData = [
            'contactID' => $contribution['contact_id'],
            'contributionID' => $contribution['contribution_id'],
            'amount' => $journal_entry['amt'],
            'contributionRecurID' => $contribution['contribution_recur_id'],
            'receiveDate' => $contribution['receive_date'],
            'trxnID' => $contribution['trxn_id'],
            'invoiceID' => $contribution['invoice_id'],
            'paymentProcessor' => $journal_entry['tntyp'],
            'response' => $journal_entry['auth_result']
        ];
        return $logData;
    }

    /**
     * 
     * Build the response log
     *
     * @param array $logData the exisitng logData.
     * @param string $status The status of the transaction. Either 'Success' or 'Failed'
     * @param array $params the contribution details.
     * @param array $result the response from the IATS server.
     * @param boolean $isRecur the recurring flag.
     * @param boolean $isRecurringJob to identify whether it's a scheduled Job or not
     * @return array
     * 
     */
    public function buildResponseLog(array $logData = [], $status, $params, $result, $isRecur = 0, $isRecurringJob = 0): array {
        $payment_method = $this->getPaymentMethod();
        if($isRecurringJob) {
            // CHeck for auth response as key is different for credit card and ACH
            if($payment_method == 'Credit Card (1st Pay)') {
                $authCode = $result['result']['auth_response'] ?? '';
            } else {
                $authCode = $result['result']['auth_code'] ?? '';
            }
            $logData['contributionId'] = $params['id'] ?? '';
            $logData['status'] = $status;
            $logData['statusCode'] = $authCode;
            $logData['remoteId'] = $result['result']['trxn_id'] ?? '';
            $logData['isRecurring'] = 1;
        } else {
            if($payment_method == 'Credit Card (1st Pay)') {
                $logData['contributionId'] = $params['contributionID'] ?? '';
                $logData['status'] = $status;
                $logData['statusCode'] = $result['data']['authResponse'] ?? '';
                $logData['remoteId'] = $params['trxn_id'] ?? '';
                $logData['isRecurring'] = $isRecur ? $isRecur : 0;
            }

            if($payment_method == 'ACH_EFT') {
                if($isRecur) {
                    $logData['contributionId'] = $params['contributionID'] ?? '';
                    $logData['status'] = $status;
                    $logData['statusCode'] = $result['AUTHORIZATIONRESULT'] ?? '';
                    $logData['remoteId'] = $result['CUSTOMERCODE'] ?? '';
                    $logData['isRecurring'] = '1';
                } else {
                    $logData['contributionId'] = $params['contributionID'] ?? '';
                    $logData['status'] = $status;
                    $logData['statusCode'] = $result['auth_result'] ?? '';
                    $logData['remoteId'] = $result['remote_id'] ?? '';
                    $logData['isRecurring'] = 0;
                }
            }
        }
        return $logData;
    }

    public function buildPostDatedContributionLog($contribution, $paymentMethod = '', $status = '', $isRecur = 0): array {
        // Log the data
        $logData = [];
        $logData = [
            "invoiceNum" => $contribution->invoice_id,
            "amount" => $contribution->total_amount,
            "paymentMethod" => $paymentMethod,
            "requestData" => json_encode($contribution),
            "contributionId" => $contribution->id,
            "status" => $status,
            "statusCode" => '',
            "remoteId" => '',
            "isRecurring" => $isRecur ?? 0,
        ];
        return $logData;
    }

    public function buildRecurringSeriesLog($recurData,  $paymentMethod = '', $status = ''): array {
        $logData = [];
        $logData = [
            "invoiceNum" => '',
            "amount" => $recurData->amount,
            "paymentMethod" => $paymentMethod,
            "requestData" => json_encode($recurData),
            "contributionId" => $recurData->id,
            "status" => $status,
            "statusCode" => '',
            "remoteId" => '',
            "isRecurring" => (int) 1,
        ];
        return $logData;
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