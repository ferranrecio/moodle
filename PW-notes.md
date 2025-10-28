# Project Week 2025-02 Notes

## Routing in course page

References: https://github.com/laurentdavid/moodle-local_routing

### General idea

I want to add some routes into the course page, so that I can have URLs like:

- course/cm/CMID/next
- course/cm/CMID/prev
- course/COURSEID/continue

### How routing works for course

- Example: http://localhost/m/PW202502/r.php/course/admin.php?courseid=2
- Examples: http://localhost/m/PW202502/r.php/course/2/manage

Our routing:

- http://localhost/m/PW202502/r.php/course/2/section/12/next
- http://localhost/m/PW202502/r.php/course/2/cm/8/next

## Generic initial state

The idea is to create a generic component for state zero and initial states.

The initial state is for the user to decide wether to do an activity that has focus mode.

Also, I will create some extra images for initial states following the same styles as the mod_data zero state ones.

## Things to do

- Continue migrating to output the choice form.
- Apply zero states to quiz, forum, choice and glossary.
- Add more padding left in the folder in course page.

- In resource, the page to download the content is super ugly unless it is an image. This should be converted in an initial state pattern with a nice download icon. Also, the page should not be full width.
