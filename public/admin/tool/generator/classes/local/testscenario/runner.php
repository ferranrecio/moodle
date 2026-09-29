<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tool_generator\local\testscenario;

use behat_admin;
use behat_data_generators;
use behat_course;
use behat_general;
use behat_user;
use core\attribute_helper;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Node\OutlineNode;
use ReflectionMethod;

/**
 * Class to process a scenario generator file.
 *
 * @package    tool_generator
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class runner {

    /** @var behat_runtime the restricted Behat runtime. */
    private behat_runtime $runtime;

    /** @var array information about the valid steps. */
    private array $validsteps;

    /**
     * Initi all composer, behat libraries and load the valid steps.
     */
    public function init() {
        $this->include_composer_libraries();
        $this->include_behat_libraries();
        $this->load_generator();
    }

    /**
     * Include composer autload.
     */
    public function include_composer_libraries() {
        global $CFG;
        if (!file_exists($CFG->dirroot . '/../vendor/autoload.php')) {
            throw new \moodle_exception('Missing composer.');
        }
        require_once($CFG->dirroot . '/../vendor/autoload.php');
        return true;
    }

    /**
     * Include all necessary behat libraries.
     */
    public function include_behat_libraries() {
        global $CFG;
        if (!class_exists('Behat\Gherkin\Lexer')) {
            throw new \moodle_exception('Missing behat classes.');
        }

        // Behat constant.
        if (!defined('BEHAT_TEST')) {
            define('BEHAT_TEST', 1);
        }

        // Behat utilities.
        require_once($CFG->libdir . '/behat/classes/util.php');
        require_once($CFG->libdir . '/behat/classes/behat_command.php');
        require_once($CFG->libdir . '/behat/behat_base.php');
        require_once("{$CFG->libdir}/tests/behat/behat_data_generators.php");
        require_once("{$CFG->dirroot}/admin/tests/behat/behat_admin.php");
        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->dirroot}/course/tests/behat/behat_course.php");
        require_once("{$CFG->dirroot}/lib/tests/behat/behat_general.php");
        require_once("{$CFG->dirroot}/lib/tests/behat/behat_transformations.php");
        require_once("{$CFG->dirroot}/user/tests/behat/behat_user.php");
        return true;
    }

    /**
     * Load all generators.
     */
    private function load_generator() {
        $allowedmethods = [
            behat_data_generators::class => null,
            behat_admin::class => ['the_following_config_values_are_set_as_admin'],
            behat_general::class => ['i_enable_plugin', 'i_disable_plugin'],
            behat_course::class => ['the_course_is_deleted'],
            behat_user::class => ['the_user_is_deleted'],
        ];
        $contexts = [
            new behat_data_generators(),
            new behat_admin(),
            new behat_general(),
            new behat_course(),
            new behat_user(),
            new \behat_transformations(),
        ];
        $this->runtime = new behat_runtime($contexts, $allowedmethods);
        $this->validsteps = $this->build_valid_steps();
    }

    /**
     * Build the step information used by the web interface.
     *
     * @return array
     */
    private function build_valid_steps(): array {
        $steps = [];
        foreach ($this->runtime->get_definitions() as $definition) {
            $method = $definition->getReflection();
            if (!$method instanceof ReflectionMethod) {
                continue;
            }
            $step = (object) [
                'given' => $definition->getPattern(),
                'name' => $method->getName(),
                'generator' => null,
                'example' => null,
            ];
            $reference = $method->getDeclaringClass()->getName() . '::' . $method->getName();
            if ($attribute = attribute_helper::instance($reference, \core\attribute\example::class)) {
                $step->example = (string) $attribute->example;
            }
            $steps[] = $step;
        }
        return $steps;
    }

    /**
     * Get all valid steps.
     * @return array the valid steps.
     */
    public function get_valid_steps(): array {
        return $this->validsteps;
    }

    /**
     * Parse a feature file.
     * @param string $content the feature file content.
     * @return parsedfeature
     */
    public function parse_feature(string $content): parsedfeature {
        return $this->parse_selected_scenarios($content);
    }

    /**
     * Parse all feature file scenarios.
     *
     * Note: if no filter is passed, it will execute only the scenarios that are not tagged.
     *
     * @param string $content the feature file content.
     * @param string $filtertag the tag to filter the scenarios.
     * @return parsedfeature
     */
    private function parse_selected_scenarios(string $content, ?string $filtertag = null): parsedfeature {
        $result = new parsedfeature();

        $parser = $this->get_parser();
        $feature = $parser->parse($content);

        // No need for background in testing scenarios because scenarios can only contain generators.
        // In the future the background can be used to define clean up steps (when clean up methods
        // are implemented).
        if ($feature->hasScenarios()) {
            $scenarios = $feature->getScenarios();
            foreach ($scenarios as $scenario) {
                // By default, we only execute scenaros that are not tagged.
                if (empty($filtertag) && !empty($scenario->getTags())) {
                    continue;
                }
                if ($filtertag && !in_array($filtertag, $scenario->getTags())) {
                    continue;
                }
                if ($scenario->getNodeType() == 'Outline') {
                    $this->parse_scenario_outline($feature, $scenario, $result);
                    continue;
                }
                $result->add_scenario($scenario->getNodeType(), $scenario->getTitle());
                $steps = $scenario->getSteps();
                foreach ($steps as $step) {
                    $result->add_step(new steprunner($this->runtime, $feature, $step));
                }
            }
        }
        return $result;
    }

    /**
     * Parse a feature file using only the scenarios with cleanup tag.
     * @param string $content the feature file content.
     * @return parsedfeature
     */
    public function parse_cleanup(string $content): parsedfeature {
        return $this->parse_selected_scenarios($content, 'cleanup');
    }

    /**
     * Parse a scenario outline.
        * @param \Behat\Gherkin\Node\FeatureNode $feature the parsed feature.
        * @param OutlineNode $scenario the scenario outline to parse.
     * @param parsedfeature $result the parsed feature to add the scenario.
     */
    private function parse_scenario_outline(
        \Behat\Gherkin\Node\FeatureNode $feature,
        OutlineNode $scenario,
        parsedfeature $result,
    ) {
        $count = 1;
        foreach ($scenario->getExamples() as $example) {
            $result->add_scenario($example->getNodeType(), $example->getOutlineTitle() . " ($count)");
            $steps = $example->getSteps();
            foreach ($steps as $step) {
                $result->add_step(new steprunner($this->runtime, $feature, $step));
            }
            $count++;
        }
    }

    /**
     * Get the parser.
     * @return Parser
     */
    private function get_parser(): Parser {
        $keywords = new ArrayKeywords([
            'en' => [
                'feature' => 'Feature',
                'background' => 'Background',
                'scenario' => 'Scenario',
                'scenario_outline' => 'Scenario Outline|Scenario Template',
                'examples' => 'Examples|Scenarios',
                'given' => 'Given',
                'when' => 'When',
                'then' => 'Then',
                'and' => 'And',
                'but' => 'But',
            ],
        ]);
        $lexer = new Lexer($keywords);
        $parser = new Parser($lexer);
        return $parser;
    }

    /**
     * Execute a parsed feature.
     * @param parsedfeature $parsedfeature the parsed feature to execute.
     * @return bool true if all steps were executed successfully.
     */
    public function execute(parsedfeature $parsedfeature): bool {
        if (!$parsedfeature->is_valid()) {
            return false;
        }
        $result = true;
        $steps = $parsedfeature->get_all_steps();
        foreach ($steps as $step) {
            $result = $step->execute() && $result;
        }
        return $result;
    }
}
