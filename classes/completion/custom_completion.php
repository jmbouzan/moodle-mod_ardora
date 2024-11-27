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
 * Custom completion for ardora
 *
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ardora\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion class for the Ardora module.
 *
 * This class extends the core activity_custom_completion to implement custom completion rules.
 */
class custom_completion extends activity_custom_completion {

    /**
     * Returns the list of custom completion rules defined for the Ardora module.
     *
     * This method specifies the custom completion rules that the Ardora module supports.
     *
     * @return array The list of custom completion rule names.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionpassgrade'];
    }

    /**
     * Calculates the completion state for the specified custom rule.
     *
     * @param string $rule The custom rule name.
     * @return int Completion status: COMPLETION_COMPLETE, COMPLETION_INCOMPLETE, or COMPLETION_RULE_NOT_APPLICABLE.
     */
    public function get_state(string $rule): int {
        if ($rule === 'completionpassgrade') {
            $passinggrade = $this->cm->customdata['gradepass'] ?? null;
            $usergrade = $this->cm->customdata['usergrade'] ?? null;
            // Validate the presence of passing grade.
            if ($passinggrade === null) {
                return COMPLETION_RULE_NOT_APPLICABLE;
            }

            // Check user grade.
            if ($usergrade === null) {
                return COMPLETION_INCOMPLETE; // No grade available yet.
            }

            // Determine completion based on grade.
            return $usergrade >= $passinggrade ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        return COMPLETION_RULE_NOT_APPLICABLE; // Rule not recognized.
    }

    /**
     * Returns the description of custom rules for display in the activity completion settings.
     *
     * @return array The descriptions of the custom rules.
     */
    public function get_custom_rule_descriptions(): array {
        $descriptions = [];

        $passinggrade = $this->cm->customdata['gradepass'] ?? 0;

        $descriptions['completionpassgrade'] = get_string('completionpassgrade_desc', 'mod_ardora', $passinggrade);

        return $descriptions;
    }

    /**
     * Specifies the sort order of custom rules.
     *
     * This determines how the custom rules are ordered in the completion settings.
     *
     * @return array The sort order of the custom rules.
     */
    public function get_sort_order(): array {
        return ['completionpassgrade' => 100];
    }
}
