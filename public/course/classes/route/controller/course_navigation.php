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

namespace core_course\route\controller;

use core\router\route;
use core\router\require_login;
use core_course\cm_info;
use core_course\modinfo;
use core_course\section_info;
use navigation_node;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use core\url;

/**
 * Class course_navigation
 *
 * @package    core_course
 * @copyright  2025 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_navigation {
    use \core\router\route_controller;

    /**
     * Go to the next element of the course.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param cm_info $cm
     * @return ResponseInterface
     */
    #[route(
        path: '/cm/{cmid}/next',
        pathtypes: [
            new \core\router\parameters\path_course_module(),
        ],
        requirelogin: new require_login(
            requirelogin: true,
            courseattributename: 'course',
        ),
    )]
    public function cm_next_element(
        ServerRequestInterface $request,
        ResponseInterface $response,
        cm_info $cm,
    ): ResponseInterface {
        $modinfo = $cm->get_modinfo();
        $section = $cm->get_section_info();

        $allsectioncms = $this->get_all_section_cms($modinfo, $section);
        $cmindex = array_search($cm, $allsectioncms, true);

        if ($cmindex === false) {
            return $this->page_not_found($request, $response);
        }

        // Last element in the section should redirect to next section page
        // so student can see the next section title and description.
        if ($cmindex + 1 >= count($allsectioncms)) {
            return $this->redirect_to_next_section($response, $modinfo, $section);
        }

        $url = $allsectioncms[$cmindex + 1]->get_url();

        return $this->redirect(
            $response,
            $url,
        );
    }

    /**
     * Get all course modules in a section in order, including also activities inside sub-sections.
     *
     * @param \core_course\modinfo $modinfo
     * @param \core_course\section_info $section
     * @return cm_info[]
     */
    private function get_all_section_cms(modinfo $modinfo, section_info $section): array {
        $sectioncms = [];
        if (!$section->uservisible) {
            return $sectioncms;
        }
        foreach ($section->get_sequence_cm_infos() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            $delegatedsection = $cm->get_delegated_section_info();
            if ($delegatedsection) {
                $sectioncms = array_merge(
                    $sectioncms,
                    $this->get_all_section_cms($modinfo, $delegatedsection),
                );
            } else {
                $sectioncms[] = $cm;
            }
        }
        return $sectioncms;
    }

    /**
     * Redirect to the next section view page.
     *
     * @param ResponseInterface $response
     * @param modinfo $modinfo
     * @param section_info $currentsection
     * @return ResponseInterface
     */
    private function redirect_to_next_section(
        ResponseInterface $response,
        modinfo $modinfo,
        section_info $currentsection,
    ): ResponseInterface {
        $nextsection = $modinfo->get_section_info($currentsection->sectionnum + 1);

        if (!$nextsection->uservisible || $nextsection->is_delegated()) {
            return $this->redirect_to_next_section($response, $modinfo, $nextsection);
        }

        if ($nextsection === null) {
            // No more sections.
            return $this->redirect(
                $response,
                new url('/course/view.php', ['id' => $modinfo->get_course()->id]),
            );
        }

        $format = course_get_format($modinfo->get_course());
        $url = $format->get_view_url($nextsection, ['navigation' => true]);
        return $this->redirect(
            $response,
            $url,
        );
    }
}
