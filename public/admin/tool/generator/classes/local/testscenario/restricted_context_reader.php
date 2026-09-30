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

use Behat\Behat\Context\Annotation\DocBlockHelper;
use Behat\Behat\Context\Environment\ContextEnvironment;
use Behat\Behat\Context\Reader\AnnotatedContextReader;
use Behat\Behat\Context\Reader\AttributeContextReader;
use Behat\Behat\Context\Reader\ContextReader;
use Behat\Behat\Definition\Context\Annotation\DefinitionAnnotationReader;
use Behat\Behat\Definition\Context\Attribute\DefinitionAttributeReader;
use Behat\Behat\Definition\Definition;
use Behat\Behat\Transformation\Context\Annotation\TransformationAnnotationReader;
use Behat\Behat\Transformation\Context\Attribute\TransformationAttributeReader;
use Behat\Behat\Transformation\Transformation;

/**
 * Reads only the Behat definitions allowed by the test scenario tool.
 *
 * @package    tool_generator
 * @copyright  2026 Ferran Recio <ferran@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restricted_context_reader implements ContextReader {
    /** @var ContextReader[] Standard Behat context readers. */
    private array $readers;

    /**
     * Constructor.
     *
     * A null method list allows every definition in that context.
     *
     * @param array $allowedmethods allowed methods by context class (<string, null|string[]>)
     */
    public function __construct(
        private readonly array $allowedmethods,
    ) {
        $docblockhelper = new DocBlockHelper();

        $annotationreader = new AnnotatedContextReader($docblockhelper);
        $annotationreader->registerAnnotationReader(new DefinitionAnnotationReader());
        $annotationreader->registerAnnotationReader(new TransformationAnnotationReader());

        $attributereader = new AttributeContextReader();
        $attributereader->registerAttributeReader(new DefinitionAttributeReader($docblockhelper));
        $attributereader->registerAttributeReader(new TransformationAttributeReader($docblockhelper));

        $this->readers = [$annotationreader, $attributereader];
    }

    /**
     * Read the allowed definitions and all transformations from a context.
     *
     * phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
     *
     * @param ContextEnvironment $environment the Behat context environment
     * @param string $contextclass the context class name
     * @return array
     */
    public function readContextCallees(ContextEnvironment $environment, $contextclass): array {
        $callees = [];
        foreach ($this->readers as $reader) {
            $callees = array_merge($callees, $reader->readContextCallees($environment, $contextclass));
        }

        return array_values(array_filter($callees, function ($callee): bool {
            if ($callee instanceof Transformation) {
                return true;
            }
            if (!$callee instanceof Definition) {
                return false;
            }

            [$classname, $methodname] = $callee->getCallable();
            if (!array_key_exists($classname, $this->allowedmethods)) {
                return false;
            }
            $methods = $this->allowedmethods[$classname];
            return $methods === null || in_array($methodname, $methods, true);
        }));
    }
}
