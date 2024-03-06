<?php

/**
 * Job.UpdateCHContributions API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_job_iats_log_cleanup_spec(&$spec) {
  $spec['log_files'] = [
    'title' => ts('Log Files'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Clean up iATS-related log files and provide status for each file.
 *
 * @param array $params An array of parameters, including optional 'log_files'.
 * @return array An array containing the job status for each log file.
 */
function civicrm_api3_job_iats_log_cleanup($params) {
  // Initialize an array to store the status of each log file.
  $jobStatus = [];

  // List of iATS related log files.
  $existingLogFiles = ['iatslog', 'iats-log-dev'];

  // Determine the list of log files to process.
  $logFiles = !empty($params['log_files']) ? explode(',', $params['log_files']) : $existingLogFiles;

  // Process each log file and update the job status array.
  foreach ($logFiles as $logFile) {
    // Trim white spaces from the log file name.
    $logFile = trim($logFile);

    // Get the status for the current log file.
    $status = getLogFileStatus($logFile, $existingLogFiles);

    // Add the status information to the jobStatus array.
    $jobStatus[] = [
      'file' => $logFile . '.csv',
      'status' => $status['status'],
      'message' => $status['message'],
    ];
  }

  // Return a CiviCRM API success response with the job status array.
  return civicrm_api3_create_success(1, [], NULL, NULL, $_nullObject, ['job_status' => $jobStatus]);
}

/**
* Get the status of a specific log file.
*
* @param string $logFile The name of the log file.
* @param array $existingLogFiles An array of existing iATS log files.
* @return array An associative array containing the status and message for the log file.
*/
function getLogFileStatus($logFile, $existingLogFiles) {
  // Check if the log file is part of the existing iATS log files.
  if (in_array($logFile, $existingLogFiles)) {
    // Attempt to clear the iATS log for the current file.
    $isCleared = CRM_Utils_Log_IatsPayment::clearIatsLog($logFile);

    // Return the status and message based on the clear operation result.
    return [
      'status' => $isCleared ? 'Success' : 'Failed',
      'message' => $isCleared ? 'Successfully cleared log' : 'Logs not created yet',
    ];
  } else {
    // The provided log file is not part of the existing iATS log files.
    return ['status' => 'Failed', 'message' => 'Incorrect file provided'];
  }
}
