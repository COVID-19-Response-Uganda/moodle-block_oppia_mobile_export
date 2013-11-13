<?php 
require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/filestorage/file_storage.php');

require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->dirroot . '/blocks/oppia_mobile_export/lib.php');
require_once($CFG->dirroot . '/blocks/oppia_mobile_export/langfilter.php');

require_once($CFG->dirroot . '/blocks/oppia_mobile_export/activity/activity.class.php');
require_once($CFG->dirroot . '/blocks/oppia_mobile_export/activity/page.php');
require_once($CFG->dirroot . '/blocks/oppia_mobile_export/activity/quiz.php');
require_once($CFG->dirroot . '/blocks/oppia_mobile_export/activity/resource.php');

require_once($CFG->libdir.'/componentlib.class.php');

$id = required_param('id',PARAM_INT);
$stylesheet = required_param('stylesheet',PARAM_TEXT);

$course = $DB->get_record('course', array('id'=>$id));

$PAGE->set_url('/blocks/oppia_mobile_export/export.php', array('id' => $id));
preload_course_contexts($id);
if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
	print_error('nocontext');
}

require_login($course);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);


$context = get_context_instance(CONTEXT_COURSE, $course->id);
$PAGE->set_context($context);
preload_course_contexts($course->id);
$modinfo = get_fast_modinfo($course);
$sections = $modinfo->get_section_info_all();
$mods = $modinfo->get_cms();

$quizzes = array();
/*-------Get course info pages/about etc----------------------*/
$thissection = $sections[0];
$sectionmods = explode(",", $thissection->sequence);
foreach ($sectionmods as $modnumber) {
		
	if (empty($modinfo->sections[0])) {
		continue;
	}
	$mod = $mods[$modnumber];
		
	if($mod->modname == 'quiz'){
		echo "\tExporting quiz: ".$mod->name."\n";

		$quiz = new mobile_activity_quiz();
		$quiz->init($course->shortname,"Pre-test",0);
		$quiz->id = $mod->id;
		$quiz->section = 0;
		$quiz->preprocess();
		if ($quiz->get_is_valid() && $quiz->get_no_questions()> 1){
			$temp = new stdClass;
			$temp->section = "Topic 0";
			$temp->name = $mod->name;
			$temp->noquestions = $quiz->get_no_questions();
			$temp->id = $mod->id;
			array_push($quizzes, $temp);
		}
	}
}

$orderno = 1;
foreach($sections as $sect) {
	$sectionmods = explode(",", $sect->sequence);
	if($sect->summary && count($sectionmods)>0){
		foreach ($sectionmods as $modnumber) {
				
			if (empty($modinfo->sections[$orderno])) {
				continue;
			}
			$mod = $mods[$modnumber];
			
			if($mod->modname == 'quiz'){
			
				$quiz = new mobile_activity_quiz();
				$quiz->init($course->shortname,$sect->summary,0);
				$quiz->id = $mod->id;
				$quiz->section = $orderno;
				$quiz->preprocess();
				if ($quiz->get_is_valid() && $quiz->get_no_questions()> 1){
					$temp = new stdClass;
					$temp->section = strip_tags($sect->summary);
					$temp->name = $mod->name;
					$temp->noquestions = $quiz->get_no_questions();
					$temp->id = $mod->id;
					array_push($quizzes, $temp);
				}
			}
		}
		$orderno++;
	}
}


if (count($quizzes)> 0){
	echo $OUTPUT->header();
	echo "<form name='selectquizzes' method='post' action='".$CFG->wwwroot."/blocks/oppia_mobile_export/export2.php'>";
	echo "<h2>Export - step 1</h2>";
	echo "<input type='hidden' name='id' value='".$COURSE->id."'>";
	echo "<input type='hidden' name='sesskey' value='".sesskey()."'>";
	echo "<input type='hidden' name='stylesheet' value='".$stylesheet."'>";
	echo "<p>Since this course contains quizzes, please select which quizzes (if any) should be a random selection of the questions available</p>";
	
	// using table not ideal but works for now
	echo "<table>";
	echo "<tr>";
	echo "<th>Section Name</th>";
	echo "<th>Quiz Title</th>";
	echo "<th>No random questions</th>";
	echo "</tr>";
	foreach ($quizzes as $quiz){
		echo "<tr>";
			echo "<td>".$quiz->section."</td>";
			echo "<td>".$quiz->name."</td>";
			echo "<td><select name='quiz_".$quiz->id."'>";
			echo "<option value='0'>Use all questions (don't randomise)</option>";
			for ($i=1; $i<$quiz->noquestions; $i++){
				echo "<option value='".$i."'>select ".$i." random questions</option>";
			}
			echo "</select></td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<input type='submit' name='submit' value='Continue'>";
	echo "</form>";
	echo $OUTPUT->footer();
} else {
	redirect($CFG->wwwroot."/blocks/oppia_mobile_export/export2.php?id=".$id."&sesskey=".sesskey()."&stylesheet=".$stylesheet);
}

?>