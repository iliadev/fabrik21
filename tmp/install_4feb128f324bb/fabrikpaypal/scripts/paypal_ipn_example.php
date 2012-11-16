<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
/**
 * Optional script for extending the Fabrik PayPal form plugin
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

/*
 * In the PayPal form plugin settings, you can select a PHP script to use for custom IPN processing.
 * You should copy this file, and use it as your starting point.  You must not change the class name.
 * During IPN processing, the PayPal plugin will create an instance of this class, and if there is a method
 * named after the 'payment_status' or 'txn_type' specified by PayPal, with payment_status_ or txn_type_ prepended
 * (like payment_type_Completed), the plugin will call your method, passing it a refernce to the current Fabrik tableModel,
 * the request params, the 'set_list' and 'err_msg'.
 *
 * The $tableModel allows you to access all the usual data about the table.  See the Fabrik code for details on
 * what you can do with this.
 *
 * The $request is just a copy of $_REQUEST, and will contain all the usual IPN keys/values PayPal send us,
 * like $request['pending_reason'] or $request['mc_shipping'] etc.
 *
 * The $set_list is an array, which contains the table updates the plugin is already going to make.  Array entries
 * will look like ...
 *    $set_list['your_status_field'] = 'Completed'
 * ... and you can change, add or remove as you see fit.  So if you have a custom field you want updated, just add
 * it to the arrays like ...
 *    $set_list['my_custom_field'] = "foo";
 * .... and the plugin will automatically add that to the UPDATE query for the row being processed.
 * (including Quote and nameQuote of fields)
 *
 * The $err_msg is used if you wish to abort processing by returning a status of something other than 'ok',
 * and will be included in any error / debug reporting done by the plugin.
 *
 * Your method MUST return either 'ok' to continue processing, or anything other than 'ok' to abort.  We suggest
 * your error code be form.paypal.ipnfailure.your_code (replace your_code with some informative code).  So, to
 * return an error and abort processing:
 *    $err_msg = "Something is horribly wrong!";
 *    return "form.paypal.ipnfailure.horribly_wrong";
 *
 * We have included simple do-nothing methods for the most common paypal_status values, and some example code
 * for sending some emails on payment_status_Pending, but it is by no means
 * and exhaustive list.  To add a new one, for instance for Voided, just create a payment_status_Voided() method.
 *
 * IMPORTANT NOTE - during development of your script, you REALLY MUST use the PayPal developer sandbox!!
 */

class fabrikPayPalIPN {

	/**
	 * sample create Invoice method, this is called at the beginning of the Paypal processing
	 * when the form is submitted. You should add your php code here to create an invoice, and
	 * set any {invoice} placeholder you want to use in the plugin's 'invoice number' parameter
	 */

	function createInvoice()
	{
		$db =& JFactory::getDBO();
		$date = JFactory::getDate();
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabsubs'.DS.'tables');
		$invoice = JTable::getInstance('Invoices', 'Table');
		$invoice->invoice_number = uniqid('', true);
		//set so we can use {invoice_number} as a placeholder in the paypal plugins options
		JRequest::setVar('invoice_number', $invoice->invoice_number);
		$invoice->amount = '5.00';
		$invoice->currency = 'GBP';
		$invoice->created_date = $date->toMySQL();
		$invoice->store();
	}

	/*
	 * Called at end of submission handling, just before the form plugin sets up the redirect to PayPal
	 * Allows you to check / add / remove / modify any of the query string options being sent to PayPal
	 * Also gives you a last chance to bail out and not do the redirect to PayPal, by returning false
	 * (and probably using one of the J! API's to put up a notification of why!)
	 */
	function checkOpts(&$opts, $formModel) {
		return true;
	}

	function payment_status_Completed($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function payment_status_Pending($tableModel, $request, &$set_list, &$err_msg) {
		$app =& JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = "%s - Payment Pending";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your payment on %s is pending. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Payment pending on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);
		return 'ok';
	}

	function payment_status_Reversed($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function payment_status_Cancelled_Reversal($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function payment_status_Refunded($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_web_accept($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_signup($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_cancel($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_modify($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_payment($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_failed($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

	function txn_type_subscr_eot($tableModel, $request, &$set_list, &$err_msg) {
		return 'ok';
	}

}

?>