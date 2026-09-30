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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Context\Environment\Reader\ContextEnvironmentReader;
use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Definition\Definition;
use Behat\Behat\Definition\DefinitionRepository;
use Behat\Behat\Definition\Pattern\PatternTransformer;
use Behat\Behat\Definition\Pattern\Policy\RegexPatternPolicy;
use Behat\Behat\Definition\Pattern\Policy\TurnipPatternPolicy;
use Behat\Behat\Definition\Search\RepositorySearchEngine;
use Behat\Behat\Definition\Translator\DefinitionTranslator;
use Behat\Behat\Definition\Translator\Translator;
use Behat\Behat\Transformation\Call\Filter\DefinitionArgumentsTransformer;
use Behat\Behat\Transformation\TransformationRepository;
use Behat\Behat\Transformation\Transformer\RepositoryArgumentTransformer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Argument\MixedArgumentOrganiser;
use Behat\Testwork\Argument\PregMatchArgumentOrganiser;
use Behat\Testwork\Call\CallCenter;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Call\Handler\RuntimeCallHandler;
use Behat\Testwork\Environment\EnvironmentManager;
use Behat\Testwork\Suite\GenericSuite;

/**
 * Minimal Behat runtime for test scenarios in the current Moodle instance.
 *
 * @package    tool_generator
 * @copyright  2026 Ferran Recio <ferran@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_runtime {
    /** @var InitializedContextEnvironment Behat environment bound to the live context objects. */
    private InitializedContextEnvironment $environment;

    /** @var DefinitionRepository repository containing only allowed definitions. */
    private DefinitionRepository $definitions;

    /** @var RepositorySearchEngine standard Behat definition search engine. */
    private RepositorySearchEngine $searchengine;

    /** @var CallCenter standard Behat call pipeline. */
    private CallCenter $callcenter;

    /**
     * Constructor.
     *
     * @param Context[] $contexts context instances to bind
     * @param array $allowedmethods allowed methods by context class (<string, null|string[]>)
     */
    public function __construct(array $contexts, array $allowedmethods) {
        $suite = new GenericSuite('tool_generator_testscenario', []);
        $this->environment = new InitializedContextEnvironment($suite);
        foreach ($contexts as $context) {
            $this->environment->registerContext($context);
        }

        $contextreader = new ContextEnvironmentReader();
        $contextreader->registerContextReader(new restricted_context_reader($allowedmethods));
        $environmentmanager = new EnvironmentManager();
        $environmentmanager->registerEnvironmentReader($contextreader);

        $patterntransformer = new PatternTransformer();
        $patterntransformer->registerPatternPolicy(new RegexPatternPolicy());
        $patterntransformer->registerPatternPolicy(new TurnipPatternPolicy());
        $translator = new Translator('en');

        $this->definitions = new DefinitionRepository($environmentmanager);
        $this->searchengine = new RepositorySearchEngine(
            $this->definitions,
            $patterntransformer,
            new DefinitionTranslator($translator),
            new PregMatchArgumentOrganiser(new MixedArgumentOrganiser()),
        );

        $this->callcenter = new CallCenter();
        $argumenttransformer = new DefinitionArgumentsTransformer();
        $argumenttransformer->registerArgumentTransformer(new RepositoryArgumentTransformer(
            new TransformationRepository($environmentmanager),
            $this->callcenter,
            $patterntransformer,
            $translator,
        ));
        $this->callcenter->registerCallFilter($argumenttransformer);
        $this->callcenter->registerCallHandler(new RuntimeCallHandler());
    }

    /**
     * Get all allowed definitions.
     *
     * @return Definition[]
     */
    public function get_definitions(): array {
        return $this->definitions->getEnvironmentDefinitions($this->environment);
    }

    /**
     * Match a step using Behat's definition search.
     *
     * @param FeatureNode $feature feature containing the step
     * @param StepNode $step step to match
     * @return DefinitionCall|null
     */
    public function create_call(FeatureNode $feature, StepNode $step): ?DefinitionCall {
        $result = $this->searchengine->searchDefinition($this->environment, $feature, $step);
        if ($result === null || !$result->hasMatch()) {
            return null;
        }

        return new DefinitionCall(
            $this->environment,
            $feature,
            $step,
            $result->getMatchedDefinition(),
            $result->getMatchedArguments(),
        );
    }

    /**
     * Execute a matched call through Behat's transformation and call pipeline.
     *
     * @param DefinitionCall $call call to execute
     * @return CallResult
     */
    public function execute(DefinitionCall $call): CallResult {
        return $this->callcenter->makeCall($call);
    }
}
