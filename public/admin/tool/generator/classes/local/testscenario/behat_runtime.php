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

// Behat runtime is meant to be executed in CLI only, this is why we need to
// setup an alternative runtime.
use Behat\Behat\Context\Annotation\DocBlockHelper;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Context\Environment\Reader\ContextEnvironmentReader;
use Behat\Behat\Context\Reader\AnnotatedContextReader;
use Behat\Behat\Context\Reader\AttributeContextReader;
use Behat\Behat\Context\Reader\ContextReader;
use Behat\Behat\Context\Reader\ContextReaderCachedPerContext;
use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Definition\Context\Annotation\DefinitionAnnotationReader;
use Behat\Behat\Definition\Context\Attribute\DefinitionAttributeReader;
use Behat\Behat\Definition\Definition;
use Behat\Behat\Definition\DefinitionRepository;
use Behat\Behat\Definition\Pattern\PatternTransformer;
use Behat\Behat\Definition\Pattern\Policy\RegexPatternPolicy;
use Behat\Behat\Definition\Pattern\Policy\TurnipPatternPolicy;
use Behat\Behat\Definition\Search\RepositorySearchEngine;
use Behat\Behat\Definition\Translator\DefinitionTranslator;
use Behat\Behat\Definition\Translator\Translator;
use Behat\Behat\Transformation\Call\Filter\DefinitionArgumentsTransformer;
use Behat\Behat\Transformation\Context\Annotation\TransformationAnnotationReader;
use Behat\Behat\Transformation\Context\Attribute\TransformationAttributeReader;
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
 * Some technical references:
 *
 * - Behat documentation: https://docs.behat.org/
 * - Feature context: https://docs.behat.org/en/latest/user_guide/context.html
 * - Argument transformation: https://behat.org/en/latest/user_guide/context/transformations.html
 * - Behat hooks: https://docs.behat.org/en/latest/user_guide/context/hooks.html
 *
 * @package    tool_generator
 * @copyright  2026 Ferran Recio <ferran@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class behat_runtime {
    /**
     * @var InitializedContextEnvironment Behat environment bound to the live context objects.
     *
     * Represents an active test execution scope holding instantiated context objects. It acts
     * as the stateful container for context instances during step execution.
     */
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
        $environmentmanager = $this->create_environment_manager($allowedmethods);
        $patterntransformer = $this->create_pattern_transformer();
        $translator = new Translator('en');

        $this->environment = $this->create_environment($contexts);
        $this->definitions = new DefinitionRepository($environmentmanager);
        $this->searchengine = $this->create_search_engine($this->definitions, $patterntransformer, $translator);
        $this->callcenter = $this->create_call_center($environmentmanager, $patterntransformer, $translator);
    }

    /**
     * Create a Behat environment bound to the given context instances.
     *
     * @param Context[] $contexts context instances to bind
     * @return InitializedContextEnvironment
     */
    private function create_environment(array $contexts): InitializedContextEnvironment {
        $environment = new InitializedContextEnvironment(new GenericSuite('tool_generator_testscenario', []));
        foreach ($contexts as $context) {
            $environment->registerContext($context);
        }
        return $environment;
    }

    /**
     * Create the environment manager that reads definitions and transformations from the contexts.
     *
     * @param array $allowedmethods allowed methods by context class (<string, null|string[]>)
     * @return EnvironmentManager
     */
    private function create_environment_manager(array $allowedmethods): EnvironmentManager {
        $contextreader = new ContextEnvironmentReader();
        foreach ($this->create_context_readers() as $reader) {
            // ContextReaderCachedPerContext acts as a caching layer for context readers, ensuring that
            // each context class is read only once per test execution.
            $contextreader->registerContextReader(
                new restricted_context_reader(new ContextReaderCachedPerContext($reader), $allowedmethods),
            );
        }

        $environmentmanager = new EnvironmentManager();
        $environmentmanager->registerEnvironmentReader($contextreader);
        return $environmentmanager;
    }

    /**
     * Create the same context readers Behat registers in its ContextExtension.
     *
     * Current Moodle steps are using only @given metadata tags. However, this
     * method is prepared to handle attribute #[Given] for the future as well.
     *
     * @return ContextReader[]
     */
    private function create_context_readers(): array {
        // DocBlockHelper parses PHP DocBlocks above context classes and methods. It strips comment
        // formatting (/** ... */) to extract raw docstrings, annotations, and descriptions.
        $docblockhelper = new DocBlockHelper();

        $annotationreader = new AnnotatedContextReader($docblockhelper);
        $annotationreader->registerAnnotationReader(new DefinitionAnnotationReader());
        $annotationreader->registerAnnotationReader(new TransformationAnnotationReader());

        $attributereader = new AttributeContextReader();
        $attributereader->registerAttributeReader(new DefinitionAttributeReader($docblockhelper));
        $attributereader->registerAttributeReader(new TransformationAttributeReader($docblockhelper));

        return [$annotationreader, $attributereader];
    }

    /**
     * Create the pattern transformer supporting regex and turnip step patterns.
     *
     * @return PatternTransformer
     */
    private function create_pattern_transformer(): PatternTransformer {
        $patterntransformer = new PatternTransformer();
        $patterntransformer->registerPatternPolicy(new RegexPatternPolicy());
        $patterntransformer->registerPatternPolicy(new TurnipPatternPolicy());
        return $patterntransformer;
    }

    /**
     * Create the engine that matches steps against the available definitions.
     *
     * @param DefinitionRepository $definitions available definitions
     * @param PatternTransformer $patterntransformer step pattern transformer
     * @param Translator $translator definition translator
     * @return RepositorySearchEngine
     */
    private function create_search_engine(
        DefinitionRepository $definitions,
        PatternTransformer $patterntransformer,
        Translator $translator,
    ): RepositorySearchEngine {
        return new RepositorySearchEngine(
            $definitions,
            $patterntransformer,
            new DefinitionTranslator($translator),
            new PregMatchArgumentOrganiser(new MixedArgumentOrganiser()),
        );
    }

    /**
     * Create the call center that transforms arguments and executes the step calls.
     *
     * @param EnvironmentManager $environmentmanager environment manager to read transformations from
     * @param PatternTransformer $patterntransformer step pattern transformer
     * @param Translator $translator transformation translator
     * @return CallCenter
     */
    private function create_call_center(
        EnvironmentManager $environmentmanager,
        PatternTransformer $patterntransformer,
        Translator $translator,
    ): CallCenter {
        $callcenter = new CallCenter();

        $argumenttransformer = new DefinitionArgumentsTransformer();

        $repositorytransformer = new RepositoryArgumentTransformer(
            new TransformationRepository($environmentmanager),
            $callcenter,
            $patterntransformer,
            $translator,
        );
        $argumenttransformer->registerArgumentTransformer($repositorytransformer);

        $callcenter->registerCallFilter($argumenttransformer);
        $callcenter->registerCallHandler(new RuntimeCallHandler());
        return $callcenter;
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
