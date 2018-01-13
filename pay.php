<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This page shows all course enrolment options for current user.
 *
 * @package    core_enrol
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once("$CFG->libdir/formslib.php");

$id = required_param('id', PARAM_INT);
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);

if (!isloggedin()) {
    $referer = get_local_referer();
    if (empty($referer)) {
	// A user that is not logged in has arrived directly on this page,
	// they should be redirected to the course page they are trying to enrol on after logging in.
	$SESSION->wantsurl = "$CFG->wwwroot/course/view.php?id=$id";
    }
    // do not use require_login here because we are usually coming from it,
    // it would also mess up the SESSION->wantsurl
    redirect(get_login_url());
}

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

// Everybody is enrolled on the frontpage
if ($course->id == SITEID) {
    redirect("$CFG->wwwroot/");
}

if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
    print_error('coursehidden');
}

$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
//$PAGE->blocks->add_region('side-post');
//$defaultblocks = array('side_pre' => array('course_list'));
//$PAGE->blocks->add_blocks($defaultblocks);

$PAGE->set_url('/enrol/hubtel/pay.php', array('id' => $course->id));

// do not allow enrols when in login-as session
if (\core\session\manager::is_loggedinas() and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
    print_error('loginasnoenrol', '', $CFG->wwwroot . '/course/view.php?id=' . $USER->loginascontext->instanceid);
}

// get all enrol forms available in this course
$enrols = enrol_get_plugins(true);
$enrolinstances = enrol_get_instances($course->id, true);
$forms = array();
foreach ($enrolinstances as $instance) {
    if (!isset($enrols[$instance->enrol])) {
	continue;
    }
    $form = $enrols[$instance->enrol]->enrol_page_hook($instance);
    if ($form) {
	$forms[$instance->id] = $form;
    }
}

//Check if user already enrolled
if (is_enrolled($context, $USER, '', true)) {
    if (!empty($SESSION->wantsurl)) {
	$destination = $SESSION->wantsurl;
	unset($SESSION->wantsurl);
    } else {
	$destination = "$CFG->wwwroot/course/view.php?id=$course->id";
    }
    redirect($destination);   // Bye!
}

$PAGE->set_context(context_system::instance());

$PAGE->set_title('Processing request...');
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('enrolmentoptions', 'enrol'));


$courserenderer = $PAGE->get_renderer('core', 'course');

make_payment($course, $id);

//Convert currency to GHS
function convertCurrency($amount, $from, $to) {
    
    if($from == 'ghs')
    {
	return $amount;
    }
    
    $data = file_get_contents("https://finance.google.com/finance/converter?a=$amount&from=$from&to=$to");
    preg_match("/<span class=bld>(.*)<\/span>/", $data, $converted);
    $converted = preg_replace("/[^0-9.]/", "", $converted[1]);
    return number_format(round($converted, 3), 2);
}

//Make the payment
function make_payment($course, $cid) {
    global $CFG, $DB, $USER;
    //Get hubtel configuration values
	$store_details = new stdClass();
	
	$store_details->currency = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'allhubtelcurrency'), '*', MUST_EXIST)->value;
	
	$store_details->storename = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'storename'), '*', MUST_EXIST)->value;
	
	$store_details->tag_line = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'tag_line'), '*', MUST_EXIST)->value;
	
	$store_details->phone = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'phone'), '*', MUST_EXIST)->value;
	
	$store_details->logo_url = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'logo_url'), '*', MUST_EXIST)->value;
	
	$store_details->clientsecret = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'clientsecret'), '*', MUST_EXIST)->value;
	
	$store_details->clientid = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'clientid'), '*', MUST_EXIST)->value;
	
    //Get course cost values
    $cost_record = $DB->get_record_sql('SELECT `cost` FROM {enrol} WHERE `courseid` = ' . $cid . ' AND `enrol` = "hubtel"', array(1));
	
	if($store_details->currency !== 'GHS')
	{
		$cost = (float) convertCurrency($cost_record->cost, $store_details->currency, "GHS");
	}
	else{
		$cost = (float) $cost_record->cost;
	}

    //Start preparing variables for the hubtel checkout form
    $invoice = array(
	'invoice' => array(
	    'items' => array(
		'item_0' => array(
		    'name' => $course->fullname,
		    'quantity' => 1,
		    'unit_price' => (string) $cost,
		    'total_price' => (string) $cost,
		    'description' => $USER->firstname . ' ' . $USER->lastname . ' order for ' . $course->fullname,
		)
	    ),
	    'total_amount' => $cost,
	    'description' => $USER->firstname . ' ' . $USER->lastname . ' order for ' . $course->fullname,
	),
	'store' => array(
	    'name' => $store_details->storename,
	    'tagline' => $store_details->tag_line,
	    'phone' => $store_details->phone,
	    "logo_url" => $store_details->logo_url,
	    'website_url' => $CFG->wwwroot,
	),
	'actions' => array(
	    'cancel_url' => $CFG->wwwroot . "/enrol/index.php?id=" . $cid,
	    'return_url' => $CFG->wwwroot . "/enrol/hubtel/ipn.php?id=" . $cid
	),
	'custom_data' => array(
	    'course_id' => $course->id,
	    'member_id' => $USER->id,
	)
    );

    $clientId = $store_details->clientid;

    $clientSecret = $store_details->clientsecret;

    $basic_auth_key = 'Basic ' . base64_encode($clientId . ':' . $clientSecret);

    $request_url = 'https://api.hubtel.com/v1/merchantaccount/onlinecheckout/invoice/create';

    $create_invoice = json_encode($invoice);


    $ch = curl_init($request_url);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $create_invoice);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Authorization: ' . $basic_auth_key,
	'Cache-Control: no-cache',
	'Content-Type: application/json',
    ));

    $result = curl_exec($ch);

    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {

	echo $error;
    } else {

	// redirect customer to checkout

	$response_param = json_decode($result);

	// var_dump($response_param);exit;

	$redirect_url = $response_param->response_text;

	header('Location: ' . $redirect_url);
    }
}

?>