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
 * Hubtel enrolments plugin settings and presets.
 *
 * @package    enrol_hubtel
 * @copyright 2017 Bright Ahiadeke
 * @author     Bright Ahiadeke - https://kwekubright.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_hubtel_settings', '', get_string('pluginname_desc', 'enrol_hubtel')));

    $settings->add(new admin_setting_configtext('enrol_hubtel/hubtelbusiness', get_string('businessemail', 'enrol_hubtel'), get_string('businessemail_desc', 'enrol_hubtel'), '', PARAM_EMAIL));

    $settings->add(new admin_setting_configtext('enrol_hubtel/clientid', get_string('clientid', 'enrol_hubtel'), get_string('clientid_desc', 'enrol_hubtel'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_hubtel/clientsecret', get_string('clientsecret', 'enrol_hubtel'), get_string('clientsecret_desc', 'enrol_hubtel'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_hubtel/storename', get_string('storename', 'enrol_hubtel'), get_string('storename_desc', 'enrol_hubtel'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_hubtel/phone', get_string('phone', 'enrol_hubtel'), get_string('phone_desc', 'enrol_hubtel'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_hubtel/tag_line', get_string('tag_line', 'enrol_hubtel'), get_string('tag_line_desc', 'enrol_hubtel'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_hubtel/logo_url', get_string('logo_url', 'enrol_hubtel'), get_string('logo_url_desc', 'enrol_hubtel'), '', PARAM_URL));

    $all_hubtel_currencies = enrol_get_plugin('hubtel')->get_currencies();
    $settings->add(new admin_setting_configselect('enrol_hubtel/allhubtelcurrency', get_string('currency', 'enrol_hubtel'), '', 'GHS', $all_hubtel_currencies));

    $settings->add(new admin_setting_configcheckbox('enrol_hubtel/mailstudents', get_string('mailstudents', 'enrol_hubtel'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_hubtel/mailteachers', get_string('mailteachers', 'enrol_hubtel'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_hubtel/mailadmins', get_string('mailadmins', 'enrol_hubtel'), '', 0));

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be enrolled any more.
    $options = array(
	ENROL_EXT_REMOVED_KEEP => get_string('extremovedkeep', 'enrol'),
	ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
	ENROL_EXT_REMOVED_UNENROL => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect('enrol_hubtel/expiredaction', get_string('expiredaction', 'enrol_hubtel'), get_string('expiredaction_help', 'enrol_hubtel'), ENROL_EXT_REMOVED_SUSPENDNOROLES, $options));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_hubtel_defaults', get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'),
	ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_hubtel/status', get_string('status', 'enrol_hubtel'), get_string('status_desc', 'enrol_hubtel'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_hubtel/cost', get_string('cost', 'enrol_hubtel'), '', 0, PARAM_FLOAT, 4));

    $hubtelcurrencies = enrol_get_plugin('hubtel')->get_currencies();
    $settings->add(new admin_setting_configselect('enrol_hubtel/currency', get_string('currency', 'enrol_hubtel'), '', 'GHS', $hubtelcurrencies));

    if (!during_initial_install()) {
	$options = get_default_enrol_roles(context_system::instance());
	$student = get_archetype_roles('student');
	$student = reset($student);
	$settings->add(new admin_setting_configselect('enrol_hubtel/roleid', get_string('defaultrole', 'enrol_hubtel'), get_string('defaultrole_desc', 'enrol_hubtel'), $student->id, $options));
    }

    $settings->add(new admin_setting_configduration('enrol_hubtel/enrolperiod', get_string('enrolperiod', 'enrol_hubtel'), get_string('enrolperiod_desc', 'enrol_hubtel'), 0));
}
