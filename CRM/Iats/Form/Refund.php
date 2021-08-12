<?php

use CRM_Iats_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Iats_Form_Refund extends CRM_Core_Form {

  /**
   * contact ID
   * @var object
   */
  protected $_contactID;

  /**
   * Test or live mode
   * @var object
   */
  protected $_isTest;

  protected $_paymentProcessorID;


  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::UPDATE)) {
      // @todo replace with throw new CRM_Core_Exception().
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $this->_paymentProcessorID = E::getPaymentProcessorByContributionID($this->_id);
    if (!$this->_paymentProcessorID) {
      CRM_Core_Error::statusBounce(ts('Payment processor not found'));
    }
    parent::preProcess();

    $this->_isTest = 0;
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $this->_isTest = 1;
    }
  }

  public function buildQuickForm() {
    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => ts('Refund'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public function postProcess() {
    // find the token for this contribution
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $this->_id));
    }
    catch (CiviCRM_API3_Exception $e) {
      // FIXME: display an error message or something ?
      throw new \Civi\Payment\Exception\PaymentProcessorException($e->getMessage());
    }

    try {
      $refundParams = [
        'payment_processor_id' => $this->_paymentProcessorID,
        'amount' => $contribution['total_amount'],
        'currency' => $contribution['currency'],
        'trxn_id' => $contribution['trxn_id'],
      ];
      $refund = civicrm_api3('PaymentProcessor', 'Refund', $refundParams)['values'];
      if ($refund['refund_status_name'] === 'Completed') {
        $payments = civicrm_api3('Payment', 'get', ['entity_id' => $params['contribution_id']]);
        if (!empty($payments['count']) && !empty($payments['values'])) {
           foreach ($payments['values'] as $payment) {
             civicrm_api3('Payment', 'cancel', [
               'id' => $payment['id'],
               'trxn_date' => date('Y-m-d H:i:s'),
             ]);
           }
         }
       }
      $refundPaymentParams = [
        'contribution_id' => $this->_id,
        'trxn_id' => $refund['refund_trxn_id'],
        'total_amount' => (-1 * $contribution['total_amount']),
        'payment_processor_id' => $this->_paymentProcessorID,
      ];
      $trxn = CRM_Financial_BAO_Payment::create($refundPaymentParams);

      CRM_Core_Session::setStatus(E::ts('Refund was processed successfully.'), 'Refund processed', 'success');

      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactID}&selectedChild=contribute"
      ));
    } catch (Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage(), NULL, 'Refund failed');
    }
  }

}
