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

namespace core\output;

use renderable;

/**
 * Class to render a dropdown dialog element.
 *
 * A dropdown dialog allows to render any arbitrary HTML into a dropdown elements triggered
 * by a button.
 *
 * @package    core
 * @category   output
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dropdown_dialog implements named_templatable, renderable {

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
     * @var array extra HTML attributes (attribute => value).
     */
    protected $attributes = [];

    /**
     * Constructor.
     *
     * @param string $buttoncontent the button content
     * @param string $dialogcontent the footer content
     * @param string|null $buttonclasses extra CSS classes
     * @param array $attributes extra html attributes (attribute => value)
     */
    public function __construct(string $buttoncontent, string $dialogcontent, ?string $buttonclasses = null, array $attributes = []) {
        $this->buttoncontent = $buttoncontent;
        $this->dialogcontent = $dialogcontent;
        if ($buttonclasses !== null) {
            $this->buttonclasses = $buttonclasses;
        }
        $this->attributes = $attributes;
    }

    /**
     * Set the dialog contents.
     *
     * @param string $dialogcontent
     */
    public function set_content(string $dialogcontent) {
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
     * Add extra classes to trigger butotn.
     *
     * @param string $buttonclasses the extra classes
     */
    public function add_classes(string $buttonclasses) {
        if (!empty($this->buttonclasses)) {
            $this->buttonclasses .= ' ';
        }
        $this->buttonclasses = $buttonclasses;
    }

    /**
     * Add extra attributes to the sticky footer element.
     *
     * @param string $atribute the attribute
     * @param string $value the value
     */
    public function add_attribute(string $atribute, string $value) {
        $this->attributes[$atribute] = $value;
    }

    /**
     * Set the button element id.
     *
     * @param string $atribute the attribute
     * @param string $value the value
     */
    public function add_button_id(string $value){
        $this->attributes['id'] = $value;
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $extras = [];
        // Id is required for the correct HTML labelling.
        if (isset($this->attributes['id'])) {
            $buttonid = $this->attributes['id'];
            unset($this->attributes['id']);
        } else {
            $buttonid = \html_writer::random_id(uniqid());

        }
        foreach ($this->attributes as $attribute => $value) {
            $extras[] = [
                'attribute' => $attribute,
                'value' => $value,
            ];
        }
        $data = [
            'buttonid' => $buttonid,
            'buttoncontent' => (string)$this->buttoncontent,
            'dialogcontent' => (string)$this->dialogcontent,
            'buttonclasses' => $this->buttonclasses,
            'extras' => $extras,
        ];
        return $data;
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string the template name
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'core/dropdown_dialog';
    }
}
