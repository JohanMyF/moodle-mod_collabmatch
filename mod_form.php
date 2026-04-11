<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Activity settings form for the collabmatch module.
 *
 * @package    mod_collabmatch
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the settings form for the collabmatch activity.
 */
class mod_collabmatch_mod_form extends moodleform_mod {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement(
            'text',
            'name',
            get_string('collabmatchname', 'mod_collabmatch'),
            ['size' => '64']
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement(
            'static',
            'descriptionguidance',
            '',
            get_string('descriptionguidance', 'mod_collabmatch')
        );

        $mform->addElement(
            'header',
            'collabmatchcontent',
            get_string('collabmatchcontent', 'mod_collabmatch')
        );

        $mform->addElement(
            'text',
            'prompttext',
            get_string('prompttext', 'mod_collabmatch'),
            ['size' => '80']
        );
        $mform->setType('prompttext', PARAM_TEXT);
        $mform->addHelpButton('prompttext', 'prompttext', 'mod_collabmatch');
        $mform->setDefault('prompttext', get_string('defaultprompttext', 'mod_collabmatch'));

        $mform->addElement(
            'textarea',
            'targetzones',
            get_string('targetzones', 'mod_collabmatch'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('targetzones', PARAM_TEXT);
        $mform->addHelpButton('targetzones', 'targetzones', 'mod_collabmatch');
        $mform->setDefault('targetzones', "South Africa\nHabitat\nPainting\nInvention");

        $mform->addElement(
            'textarea',
            'matchpairs',
            get_string('matchpairs', 'mod_collabmatch'),
            ['rows' => 8, 'cols' => 60]
        );
        $mform->setType('matchpairs', PARAM_TEXT);
        $mform->addHelpButton('matchpairs', 'matchpairs', 'mod_collabmatch');
        $mform->setDefault(
            'matchpairs',
            "Springbok|South Africa\nLion|Habitat\nMona Lisa|Painting\nEdison|Invention"
        );

        $mform->addElement(
            'advcheckbox',
            'timerenabled',
            get_string('timerenabled', 'mod_collabmatch'),
            get_string('timerenabled_desc', 'mod_collabmatch')
        );
        $mform->setDefault('timerenabled', 1);
        $mform->addHelpButton('timerenabled', 'timerenabled', 'mod_collabmatch');

        $mform->addElement(
            'text',
            'timeseconds',
            get_string('timeseconds', 'mod_collabmatch'),
            ['size' => '8']
        );
        $mform->setType('timeseconds', PARAM_INT);
        $mform->setDefault('timeseconds', 60);
        $mform->addHelpButton('timeseconds', 'timeseconds', 'mod_collabmatch');
        $mform->hideIf('timeseconds', 'timerenabled', 'notchecked');

        $mform->addElement(
            'textarea',
            'howitworkstext',
            get_string('howitworkstext', 'mod_collabmatch'),
            ['rows' => 4, 'cols' => 70]
        );
        $mform->setType('howitworkstext', PARAM_TEXT);
        $mform->addHelpButton('howitworkstext', 'howitworkstext', 'mod_collabmatch');
        $mform->setDefault('howitworkstext', get_string('defaulthowitworkstext', 'mod_collabmatch'));

        $mform->addElement(
            'static',
            'collabmatchteacherinfo',
            '',
            get_string('teacherinfotext', 'mod_collabmatch')
        );

        $this->standard_grading_coursemodule_elements();

        $mform->addElement(
            'static',
            'gradingguidance',
            '',
            get_string('gradingguidance', 'mod_collabmatch')
        );

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Custom validation for the form.
     *
     * @param array $data Submitted form data
     * @param array $files Uploaded files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim((string)$data['prompttext']) === '') {
            $errors['prompttext'] = get_string('errorpromptrequired', 'mod_collabmatch');
        }

        $zonelines = preg_split('/\r\n|\r|\n/', (string)$data['targetzones']);
        $cleanzones = [];
        foreach ($zonelines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleanzones[] = $line;
            }
        }
        if (count($cleanzones) < 1) {
            $errors['targetzones'] = get_string('errorzonecount', 'mod_collabmatch');
        }

        $pairlines = preg_split('/\r\n|\r|\n/', (string)$data['matchpairs']);
        $cleanpairs = [];
        foreach ($pairlines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleanpairs[] = $line;
            }
        }

        if (count($cleanpairs) < 1) {
            $errors['matchpairs'] = get_string('errorpaircount', 'mod_collabmatch');
        } else {
            foreach ($cleanpairs as $pairline) {
                if (substr_count($pairline, '|') !== 1) {
                    $errors['matchpairs'] = get_string('errorpairformat', 'mod_collabmatch');
                    break;
                }

                [$item, $zone] = array_map('trim', explode('|', $pairline, 2));
                if ($item === '' || $zone === '') {
                    $errors['matchpairs'] = get_string('errorpairemptyparts', 'mod_collabmatch');
                    break;
                }
            }
        }

        if (!empty($data['timerenabled'])) {
            $timeseconds = (int)($data['timeseconds'] ?? 0);
            if ($timeseconds < 5) {
                $errors['timeseconds'] = get_string('errortimesecondsmin', 'mod_collabmatch');
            }
            if ($timeseconds > 600) {
                $errors['timeseconds'] = get_string('errortimesecondsmax', 'mod_collabmatch');
            }
        }

        if (trim((string)($data['howitworkstext'] ?? '')) === '') {
            $errors['howitworkstext'] = get_string('errorhowitworkstextrequired', 'mod_collabmatch');
        }

        return $errors;
    }
}
