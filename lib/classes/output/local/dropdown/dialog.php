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

namespace core\output\local\dropdown;

use core\output\named_templatable;
use renderable;

/**
 * Class to render a dropdown dialog element.
 *
 * A dropdown dialog allows to render any arbitrary HTML into a dropdown elements triggered
 * by a button.
 *
 * @package    core
 * @category   output
 * @copyright  2023 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dialog implements named_templatable, renderable
{
    /** Dropdown dialog positions. */
    public const POSITION = [
        'START' => 'dropdown-menu-left',
        'END' => 'dropdown-menu-right',
    ];

    /** Dropdown dialog positions. */
    public const WIDTH = [
        'DEFAULT' => '',
        'BIG' => 'dialog-big',
        'SMALL' => 'dialog-small',
    ];


    /**
     * @var string content of dialog.
     */
    protected $dialogcontent = '';

    /**
     * @var bool if the footer should auto enable or not.
     */
    protected $buttoncontent = true;

    /**
     * @var string trigger button CSS classes.
     */
    protected $buttonclasses = '';

    /**
     * @var string component CSS classes.
     */
    protected $classes = '';

    /**
     * @var string the dropdown position.
     */
    protected $dropdownposition = self::POSITION['START'];

    /**
     * @var string dropdown preferred width.
     */
    protected $dropdownwidth = self::WIDTH['DEFAULT'];


    /**
     * @var array extra HTML attributes (attribute => value).
     */
    protected $extras = [];

    /**
     * Constructor.
     *
     * The definition object could contain the following keys:
     * - classes: component CSS classes.
     * - buttonclasses: the button CSS classes.
     * - dialogwidth: the dropdown width.
     * - extras: extra HTML attributes (attribute => value).
     *
     * @param string $buttoncontent the button content
     * @param string $dialogcontent the footer content
     * @param array $definition an optional array of the element definition
     */
    public function __construct(string $buttoncontent, string $dialogcontent, array $definition = [])
    {
        $this->buttoncontent = $buttoncontent;
        $this->dialogcontent = $dialogcontent;
        if (isset($definition['classes'])) {
            $this->classes = $definition['classes'];
        }
        if (isset($definition['buttonclasses'])) {
            $this->buttonclasses = $definition['buttonclasses'];
        }
        if (isset($definition['extras'])) {
            $this->extras = $definition['extras'];
        }
        if (isset($definition['dialogwidth'])) {
            $this->dropdownwidth = $definition['dialogwidth'];
        }
    }

    /**
     * Set the dialog contents.
     *
     * @param string $dialogcontent
     */
    public function set_content(string $dialogcontent)
    {
        $this->dialogcontent = $dialogcontent;
    }

    /**
     * Set the button contents.
     *
     * @param string $buttoncontent
     */
    public function set_button(string $buttoncontent)
    {
        $this->buttoncontent = $buttoncontent;
    }

    /**
     * Set the dialog width.
     *
     * @param string $width
     */
    public function set_dialog_width(string $width) {
        $this->dropdownwidth = $width;
    }

    /**
     * Add extra classes to trigger butotn.
     *
     * @param string $buttonclasses the extra classes
     */
    public function set_button_classes(string $buttonclasses)
    {
        $this->buttonclasses = $buttonclasses;
    }

    /**
     * Add extra classes to the component.
     *
     * @param string $classes the extra classes
     */
    public function set_classes(string $classes) {
        $this->classes = $classes;
    }

    /**
     * Add extra extras to the sticky footer element.
     *
     * @param string $atribute the extra attribute
     * @param string $value the value
     */
    public function add_extra(string $atribute, string $value)
    {
        $this->extras[$atribute] = $value;
    }

    /**
     * Set the button element id.
     *
     * @param string $atribute the attribute
     * @param string $value the value
     */
    public function add_button_id(string $value)
    {
        $this->extras['id'] = $value;
    }

    /**
     * Set the dropdown position.
     * @param string $position the position
     */
    public function set_position(string $position)
    {
        $this->dropdownposition = $position;
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): array {
        $extras = [];
        foreach ($this->extras as $attribute => $value) {
            $extras[] = [
                'attribute' => $attribute,
                'value' => $value,
            ];
        }
        $data = [
            // Id is required for the correct HTML labelling.
            'buttonid' => \html_writer::random_id('dropwdownbutton_'),
            'buttoncontent' => (string) $this->buttoncontent,
            'dialogcontent' => (string) $this->dialogcontent,
            'classes' => $this->classes,
            'buttonclasses' => $this->buttonclasses,
            'dialogclasses' => $this->dropdownwidth,
            'extras' => $extras,
        ];
        // Bootstrap 4 dropdown position still uses left and right literals.
        $data["position"] = $this->dropdownposition;
        if (right_to_left()) {
            $rltposition = [
                self::POSITION['START'] => self::POSITION['END'],
                // TODO: fix end alignment in ltr.
                self::POSITION['END'] => self::POSITION['END'],
            ];
            $data["position"] = $rltposition[$this->dropdownposition];
        }
        return $data;
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string the template name
     */
    public function get_template_name(\renderer_base $renderer): string
    {
        return 'core/local/dropdown/dialog';
    }
}
