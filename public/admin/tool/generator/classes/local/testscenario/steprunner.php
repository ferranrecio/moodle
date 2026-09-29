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

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Class to validate and process a scenario step.
 *
 * @package    tool_generator
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class steprunner {
    /** @var behat_runtime the restricted Behat runtime. */
    private behat_runtime $runtime;

    /** @var DefinitionCall|null the matched Behat call. */
    private ?DefinitionCall $call = null;

    /** @var StepNode the step node to process. */
    private StepNode $stepnode;

    /** @var bool if the step is valid. */
    private bool $isvalid = false;

    /** @var bool if the step has been executed. */
    private bool $executed = false;

    /** @var string the error message if any. */
    private string $error = '';

    /**
     * Constructor.
     * @param behat_runtime $runtime the restricted Behat runtime
     * @param FeatureNode $feature the feature containing the step
     * @param StepNode $stepnode the step node to process.
     */
    public function __construct(behat_runtime $runtime, FeatureNode $feature, StepNode $stepnode) {
        $this->runtime = $runtime;
        $this->stepnode = $stepnode;
        $this->call = $runtime->create_call($feature, $stepnode);
        if ($this->call !== null) {
            $this->isvalid = true;
        } else {
            $this->error = get_string('testscenario_invalidstep', 'tool_generator');
        }
    }

    /**
     * Return if the step is valid.
     * @return bool
     */
    public function is_valid(): bool {
        return $this->isvalid;
    }

    /**
     * Return if the step has been executed.
     * @return bool
     */
    public function is_executed(): bool {
        return $this->executed;
    }

    /**
     * Return the step text.
     * @return string
     */
    public function get_text(): string {
        return $this->stepnode->getText();
    }

    /**
     * Return the step error message.
     * @return string
     */
    public function get_error(): string {
        return $this->error;
    }

    /**
     * Return the step arguments as string.
     * @return string
     */
    public function get_arguments_string(): string {
        $result = '';
        foreach ($this->stepnode->getArguments() as $argument) {
            if ($argument instanceof TableNode) {
                $result .= $argument->getTableAsString();
            }
        }
        return $result;
    }

    /**
     * Execute the step.
     * @return bool if the step is executed or not.
     */
    public function execute(): bool {
        if (!$this->isvalid) {
            return false;
        }
        $this->executed = true;
        $result = $this->runtime->execute($this->call);
        if ($result->hasException()) {
            $this->error = $result->getException()->getMessage();
            $this->isvalid = false;
            return false;
        }
        return true;
    }
}
