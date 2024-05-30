# /home/ferran/moodles/MDL-80191/moodle
# php admin/cli/delete_course.php

bkfile=$(php admin/cli/backup.php --courseid=2 --destination=/home/ferran/moodles/MDL-80191/moodle | grep "^Writing " | sed 's/^Writing //')
echo "Backupfile: $bkfile"

# php admin/cli/restore_backup.php --file=$bkfile --categoryid=1
# exit 1

output=$(php admin/cli/restore_backup.php --file=$bkfile --categoryid=1)
course_id=$(echo "$output" | grep -oP "== Restored course ID: \K\d+")
echo "Created course ID: $course_id"

echo ""
echo "http://localhost/m/MDL-80191/course/view.php?id=$course_id"

echo ""
echo "To delete the course run:"
echo "php admin/cli/delete_course.php --courseid=$course_id --non-interactive"

echo ""
read -p "Press enter to delete the course..."
rm $bkfile
php admin/cli/delete_course.php --courseid=$course_id --non-interactive
