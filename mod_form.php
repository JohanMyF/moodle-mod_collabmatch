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
 * Teacher-facing settings include:
 * - activity name
 * - description / instructions
 * - prompt / instruction shown inside the activity
 * - target zones
 * - item-to-zone pairs
 * - standard Moodle grading settings
 *
 * Example pair line:
 *   Springbok|South Africa
 *
 * Meaning:
 *   Item = Springbok
 *   Correct zone = South Africa
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

        /*
         * -------------------------------------------------------------
         * General section
         * -------------------------------------------------------------
         */
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement(
            'text',
            'name',
            get_string('collabmatchname', 'collabmatch'),
            ['size' => '64']
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        /*
         * This adds:
         * - Description editor
         * - "Display description on course page"
         */
        $this->standard_intro_elements();

        $mform->addElement(
            'static',
            'descriptionguidance',
            '',
            'Tip: Put full learner instructions in the Description box above and tick "Display description on course page" if you want learners to see the instructions before opening the activity.'
        );

        /*
         * -------------------------------------------------------------
         * Collaborative Match content settings
         * -------------------------------------------------------------
         */
        $mform->addElement('header', 'collabmatchcontent', get_string('pluginname', 'collabmatch'));

        $mform->addElement(
            'text',
            'prompttext',
            get_string('prompttext', 'collabmatch'),
            ['size' => '80']
        );
        $mform->setType('prompttext', PARAM_TEXT);
        $mform->addHelpButton('prompttext', 'prompttext', 'collabmatch');
        $mform->setDefault('prompttext', 'Choose the most appropriate collaborative move.');

        $mform->addElement(
            'textarea',
            'targetzones',
            get_string('targetzones', 'collabmatch'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('targetzones', PARAM_TEXT);
        $mform->addHelpButton('targetzones', 'targetzones', 'collabmatch');
        $mform->setDefault(
            'targetzones',
            "South Africa\nHabitat\nPainting\nInvention"
        );

        $mform->addElement(
            'textarea',
            'matchpairs',
            get_string('matchpairs', 'collabmatch'),
            ['rows' => 8, 'cols' => 60]
        );
        $mform->setType('matchpairs', PARAM_TEXT);
        $mform->addHelpButton('matchpairs', 'matchpairs', 'collabmatch');
        $mform->setDefault(
            'matchpairs',
            "Springbok|South Africa\nLion|Habitat\nMona Lisa|Painting\nEdison|Invention"
        );

        $mform->addElement(
            'static',
            'collabmatchteacherinfo',
            '',
            get_string('teacherinfotext', 'collabmatch')
        );

        /*
         * -------------------------------------------------------------
         * Standard Moodle grading section
         * -------------------------------------------------------------
         *
         * This is the key change.
         * It gives Moodle the official grading controls needed for:
         * - Maximum grade
         * - Grade to pass
         * - Passing grade completion
         */
        $this->standard_grading_coursemodule_elements();

        $mform->addElement(
            'static',
            'gradingguidance',
            '',
            'Use Moodle’s standard grading controls above. Set the maximum grade there, and if you want completion by pass, set the Grade to pass in the gradebook or grading settings.'
        );

        /*
         * -------------------------------------------------------------
         * Standard course module settings
         * -------------------------------------------------------------
         */
        $this->standard_coursemodule_elements();

        /*
         * -------------------------------------------------------------
         * Standard action buttons
         * -------------------------------------------------------------
         */
        $this->add_action_buttons();
    }

    /**
     * Custom validation for the form.
     *
     * We validate:
     * - prompt text must not be empty
     * - at least one zone must exist
     * - at least one pair must exist
     * - each pair line must contain exactly one pipe separator
     *
     * @param array $data Submitted form data
     * @param array $files Uploaded files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /*
         * Prompt text
         */
        if (trim($data['prompttext']) === '') {
            $errors['prompttext'] = 'Please enter a prompt or instruction for learners.';
        }

        /*
         * Target zones
         */
        $zonelines = preg_split('/\r\n|\r|\n/', $data['targetzones']);
        $cleanzones = [];

        foreach ($zonelines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleanzones[] = $line;
            }
        }

        if (count($cleanzones) < 1) {
            $errors['targetzones'] = 'Please enter at least one target zone.';
        }

        /*
         * Match pairs
         */
        $pairlines = preg_split('/\r\n|\r|\n/', $data['matchpairs']);
        $cleanpairs = [];

        foreach ($pairlines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $cleanpairs[] = $line;
            }
        }

        if (count($cleanpairs) < 1) {
            $errors['matchpairs'] = 'Please enter at least one match pair.';
        } else {
            foreach ($cleanpairs as $pairline) {
                if (substr_count($pairline, '|') !== 1) {
                    $errors['matchpairs'] = 'Each match pair must use exactly one pipe character, like this: Springbok|South Africa';
                    break;
                }

                list($item, $zone) = array_map('trim', explode('|', $pairline, 2));

                if ($item === '' || $zone === '') {
                    $errors['matchpairs'] = 'Each match pair must contain both an item and a correct zone.';
                    break;
                }
            }
        }

        return $errors;
    }
}