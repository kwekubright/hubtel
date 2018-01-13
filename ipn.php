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
 * Listens for Instant Payment Notification from HubTel
 *
 * This script waits for Payment notification from HubTel,
 * then double checks that data by sending it back to HubTel.
 * If HubTel verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_hubtel
 * @copyright 2017 Bright Ahiadeke
 * @author     Bright Ahiadeke - based on code by others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//define('NO_DEBUG_DISPLAY', false);

require("../../config.php"); 
require_once("lib.php");
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

//Get hubtel configuration values
$store_details = new stdClass();

$store_details->clientsecret = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'clientsecret'), '*', MUST_EXIST)->value;

$store_details->clientid = $DB->get_record('config_plugins', array('plugin' => 'enrol_hubtel', 'name' => 'clientid'), '*', MUST_EXIST)->value;


$clientId = $store_details->clientid;
$clientSecret = $store_details->clientsecret;
$basic_auth_key = 'Basic ' . base64_encode($clientId . ':' . $clientSecret);
$request_url = "api.hubtel.com/v1/merchantaccount/onlinecheckout/invoice/status/" . $_GET['token'];


$ch = curl_init($request_url);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: ' . $basic_auth_key,
    'Cache-Control: no-cache',
    'Content-Type: application/json',
));

//echo curl_setopt($ch, CURLOPT_POSTFIELDS, $create_invoice);exit;

$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo $error;
} else {
    $response_param = json_decode($result);
	

    if ($response_param->status == "completed") {

	$custom_data = $response_param->custom_data;
	
	$course_id = enrol_me_now($custom_data->course_id, $response_param);
    }
}

redirect("$CFG->wwwroot/enrol/hubtel/return.php?id=" . $course_id);

function enrol_me_now($cid, $response_param) {

    if ($cid == "cost") {
	return;
    };

    global $CFG, $USER, $OUTPUT, $PAGE, $DB;
    //Use the queried course's full name for the item_name field.	
	$data_ = new stdClass();
	$data_->course_id = $cid;
	
	$data = new stdClass();
    $data->user_id = $USER->id;
    $data->token = optional_param('token', null, PARAM_TEXT);
    $data->receipt_url = $response_param->receipt_url;
    $data->date = time();
	
    $invoice = $response_param->invoice;

    $customer_details = $response_param->customer;

    $data->payer_name = $customer_details->name;
    $data->payer_phone = $customer_details->phone;
    $invoice_details = $response_param->invoice;

    $data->items = (string) serialize($invoice_details->items);

    // Required for message_send.
    $PAGE->set_context(context_system::instance());

    /// get the user and course records
    if (!$user = $DB->get_record("user", array("id" => $data->user_id))) {
	\enrol_hubtel\util::message_hubtel_error_to_admin("Not a valid user id", $data);
	echo 1;
	die;
    }

    if (!$course = $DB->get_record("course", array("id" => $data_->course_id))) {
	\enrol_hubtel\util::message_hubtel_error_to_admin("Not a valid course id", $data);
	echo 2;
	die;
    }

    if (!$context = context_course::instance($course->id, IGNORE_MISSING)) {
	\enrol_hubtel\util::message_hubtel_error_to_admin("Not a valid context id", $data);
	echo 3;
	die;
    }

    // Now that the course/context has been validated, we can set it. Not that it's wonderful
    // to set contexts more than once but system->course switches are accepted.
    // Required for message_send.
    $PAGE->set_context($context);


    if (!$plugin_instance = $DB->get_record("enrol", array("courseid" => $data_->course_id, "status" => 0, "enrol" => "hubtel"))) {
	\enrol_hubtel\util::message_hubtel_error_to_admin("Not a valid instance id", $data);
	echo 4;
	die;
    }

    $plugin = enrol_get_plugin('hubtel');

    //Lets check if record for this token already exists. 
    if (!$DB->record_exists("hubtel_payment", array("token" => $data->token))) {
	$DB->insert_record("hubtel_payment", $data);
    }

    if ($plugin_instance->enrolperiod) {
	$timestart = time();
	$timeend = $timestart + $plugin_instance->enrolperiod;
    } else {
	$timestart = 0;
	$timeend = 0;
    }

    // Enrol user
	//Lets check if record for this token already exists. 
    if (!$DB->record_exists("hubtel_payment", array("token" => $data->token))) {
		$plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);
	}

    return $cid;
}
