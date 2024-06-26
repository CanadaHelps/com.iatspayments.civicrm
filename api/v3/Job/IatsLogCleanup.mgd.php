<?php

/**
 * @file
 * This file declares a managed database record of type "Job".
 */

// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
  0 =>
  array(
    'name' => 'Cron:Job.IatsLogCleanup',
    'entity' => 'Job',
    'params' =>
    array(
      'version' => 3,
      'name' => 'iATS Log Cleanup',
      'description' => 'Cleanup Internal CSV Logs Monthly/Weekly ',
      'run_frequency' => 'Monthly',
      'api_entity' => 'Job',
      'api_action' => 'iats_log_cleanup',
      'parameters' => '',
    ),
    'update' => 'always',
  ),
);
