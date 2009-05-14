<?php // $Id: lib.php,v 1.76 2009/04/21 21:17:23 skodak Exp $

// Graph size
$SURVEY_GHEIGHT = 500;
$SURVEY_GWIDTH  = 900;

$SURVEY_QTYPE = array (
        "-3" => "Virtual Actual and Preferred",
        "-2" => "Virtual Preferred",
        "-1" => "Virtual Actual",
         "0" => "Text",
         "1" => "Actual",
         "2" => "Preferred",
         "3" => "Actual and Preferred",
        );


define("SURVEY_COLLES_ACTUAL",           "1");
define("SURVEY_COLLES_PREFERRED",        "2");
define("SURVEY_COLLES_PREFERRED_ACTUAL", "3");
define("SURVEY_ATTLS",                   "4");
define("SURVEY_CIQ",                     "5");


// STANDARD FUNCTIONS ////////////////////////////////////////////////////////

function survey_add_instance($survey) {
    global $DB;
// Given an object containing all the necessary data, 
// (defined by the form in mod_form.php) this function 
// will create a new instance and return the id number 
// of the new instance.

    if (!$template = $DB->get_record("survey", array("id"=>$survey->template))) {
        return 0;
    }

    $survey->questions    = $template->questions; 
    $survey->timecreated  = time();
    $survey->timemodified = $survey->timecreated;

    return $DB->insert_record("survey", $survey);

}


function survey_update_instance($survey) {
    global $DB;
// Given an object containing all the necessary data, 
// (defined by the form in mod_form.php) this function 
// will update an existing instance with new data.

    if (!$template = $DB->get_record("survey", array("id"=>$survey->template))) {
        return 0;
    }

    $survey->id           = $survey->instance; 
    $survey->questions    = $template->questions; 
    $survey->timemodified = time();

    return $DB->update_record("survey", $survey);
}

function survey_delete_instance($id) {
    global $DB;
// Given an ID of an instance of this module, 
// this function will permanently delete the instance 
// and any data that depends on it.  

    if (! $survey = $DB->get_record("survey", array("id"=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("survey_analysis", array("survey"=>$survey->id))) {
        $result = false;
    }

    if (! $DB->delete_records("survey_answers", array("survey"=>$survey->id))) {
        $result = false;
    }

    if (! $DB->delete_records("survey", array("id"=>$survey->id))) {
        $result = false;
    }

    return $result;
}

function survey_user_outline($course, $user, $mod, $survey) {
    global $DB;

    if ($answers = $DB->get_records("survey_answers", array('survey'=>$survey->id, 'userid'=>$user->id))) {
        $lastanswer = array_pop($answers);

        $result = new object();
        $result->info = get_string("done", "survey");
        $result->time = $lastanswer->time;
        return $result;
    }
    return NULL;
}


function survey_user_complete($course, $user, $mod, $survey) {
    global $CFG, $DB;

    if (survey_already_done($survey->id, $user->id)) {
        if ($survey->template == SURVEY_CIQ) { // print out answers for critical incidents
            $table = NULL;
            $table->align = array("left", "left");

            $questions = $DB->get_records_list("survey_questions", "id", explode(',', $survey->questions));
            $questionorder = explode(",", $survey->questions);
            
            foreach ($questionorder as $key=>$val) {
                $question = $questions[$val];
                $questiontext = get_string($question->shorttext, "survey");
                
                if ($answer = survey_get_user_answer($survey->id, $question->id, $user->id)) {
                    $answertext = "$answer->answer1";
                } else {
                    $answertext = "No answer";
                }
                $table->data[] = array("<b>$questiontext</b>", $answertext);
            }
            print_table($table);
            
        } else {
        
            survey_print_graph("id=$mod->id&amp;sid=$user->id&amp;type=student.png");
        }
        
    } else {
        print_string("notdone", "survey");
    }
}

function survey_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $DB;

    $modinfo = get_fast_modinfo($course);
    $ids = array();
    foreach ($modinfo->cms as $cm) {
        if ($cm->modname != 'survey') {
            continue;
        }
        if (!$cm->uservisible) {
            continue;
        }
        $ids[$cm->instance] = $cm->instance;
    }

    if (!$ids) {
        return false;
    }

    $slist = implode(',', $ids); // there should not be hundreds of glossaries in one course, right?

    if (!$rs = $DB->get_recordset_sql("SELECT sa.userid, sa.survey, MAX(sa.time) AS time,
                                              u.firstname, u.lastname, u.email, u.picture
                                         FROM {survey_answers} sa
                                         JOIN {user} u ON u.id = sa.userid
                                        WHERE sa.survey IN ($slist) AND sa.time > ?
                                     GROUP BY sa.userid, sa.survey, u.firstname, u.lastname, u.email, u.picture
                                     ORDER BY time ASC", array($timestart))) {
        return false;
    }

    $surveys = array();

    foreach ($rs as $survey) {
        $cm = $modinfo->instances['survey'][$survey->survey];
        $survey->name = $cm->name;
        $survey->cmid = $cm->id;
        $surveys[] = $survey;
    } 
    $rs->close();

    if (!$surveys) {
        return false;
    }

    print_headline(get_string('newsurveyresponses', 'survey').':');
    foreach ($surveys as $survey) {
        $url = $CFG->wwwroot.'/mod/survey/view.php?id='.$survey->cmid;
        print_recent_activity_note($survey->time, $survey, $survey->name, $url, false, $viewfullnames);
    }
 
    return true;
}

function survey_get_participants($surveyid) {
//Returns the users with data in one survey
//(users with records in survey_analysis and survey_answers, students)
    global $DB;

    //Get students from survey_analysis
    $st_analysis = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                           FROM {user} u, {survey_analysis} a
                                          WHERE a.survey = ? AND
                                                u.id = a.userid", array($surveyid));
    //Get students from survey_answers
    $st_answers = $DB->get_records_sql("SELECT DISTINCT u.id, u.id
                                          FROM {user} u, {survey_answers} a
                                         WHERE a.survey = ? AND
                                               u.id = a.userid", array($surveyid));

    //Add st_answers to st_analysis
    if ($st_answers) {
        foreach ($st_answers as $st_answer) {
            $st_analysis[$st_answer->id] = $st_answer;
        }
    }
    //Return st_analysis array (it contains an array of unique users)
    return ($st_analysis);
}

// SQL FUNCTIONS ////////////////////////////////////////////////////////


function survey_log_info($log) {
    global $DB;
    return $DB->get_record_sql("SELECT s.name, u.firstname, u.lastname, u.picture
                                  FROM {survey} s, {user} u
                                 WHERE s.id = ?  AND u.id = ?", array($log->info, $log->userid));
}

function survey_get_responses($surveyid, $groupid, $groupingid) {
    global $DB;

    $params = array('surveyid'=>$surveyid, 'groupid'=>$groupid, 'groupingid'=>$groupingid); 

    if ($groupid) {
        $groupsjoin = "JOIN {groups_members} gm ON u.id = gm.userid AND gm.groupid = :groupid ";

    } else if ($groupingid) {
        $groupsjoin = "JOIN {groups_members} gm ON u.id = gm.userid
                       JOIN {groupings_groups} gg ON gm.groupid = gg.groupid AND gg.groupingid = :groupingid ";
    } else {
        $groupsjoin = "";
    }

    return $DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, u.picture, MAX(a.time) as time
                                   FROM {survey_answers} a
                                   JOIN {user} u ON a.userid = u.id
                            $groupsjoin
                                  WHERE a.survey = :surveyid
                               GROUP BY u.id, u.firstname, u.lastname, u.picture
                               ORDER BY time ASC", $params);
}

function survey_get_analysis($survey, $user) {
    global $DB;

    return $DB->get_record_sql("SELECT notes 
                                  FROM {survey_analysis}
                                 WHERE survey=? AND userid=?", array($survey, $user));
}

function survey_update_analysis($survey, $user, $notes) {
    global $DB;

    return $DB->execute("UPDATE {survey_analysis}
                            SET notes=? 
                          WHERE survey=? 
                            AND userid=?", array($notes, $survey, $user));
}


function survey_get_user_answers($surveyid, $questionid, $groupid, $sort="sa.answer1,sa.answer2 ASC") {
    global $DB;

    $params = array('surveyid'=>$surveyid, 'questionid'=>$questionid, 'groupid'=>$groupid);

    if ($groupid) {
        $groupsql = "AND gm.groupid = :groupid AND u.id = gm.userid";
    } else {
        $groupsql = "";
    }

    return $DB->get_records_sql("SELECT sa.*,u.firstname,u.lastname,u.picture 
                                   FROM {survey_answers} sa,  {user} u, {groups_members} gm 
                                  WHERE sa.survey = :surveyid 
                                        AND sa.question = :questionid 
                                        AND u.id = sa.userid $groupsql
                               ORDER BY $sort", $params);
}

function survey_get_user_answer($surveyid, $questionid, $userid) {
    global $DB;

    return $DB->get_record_sql("SELECT sa.* 
                                  FROM {survey_answers} sa
                                 WHERE sa.survey = ? 
                                       AND sa.question = ? 
                                       AND sa.userid = ?", array($surveyid, $questionid, $userid));
}

// MODULE FUNCTIONS ////////////////////////////////////////////////////////

function survey_add_analysis($survey, $user, $notes) {
    global $DB;

    $record = new object();
    $record->survey = $survey;
    $record->userid = $user;
    $record->notes = $notes;

    return $DB->insert_record("survey_analysis", $record, false);
}

function survey_already_done($survey, $user) {
    global $DB;

    return $DB->record_exists("survey_answers", array("survey"=>$survey, "userid"=>$user));
}

function survey_count_responses($surveyid, $groupid, $groupingid) {
    if ($responses = survey_get_responses($surveyid, $groupid, $groupingid)) {
        return count($responses);
    } else {
        return 0;
    }
}


function survey_print_all_responses($cmid, $results, $courseid) {

    $table = new object();
    $table->head  = array ("", get_string("name"),  get_string("time"));
    $table->align = array ("", "left", "left");
    $table->size = array (35, "", "" );

    foreach ($results as $a) {
        $table->data[] = array(print_user_picture($a->id, $courseid, $a->picture, false, true, false),
               "<a href=\"report.php?action=student&amp;student=$a->id&amp;id=$cmid\">".fullname($a)."</a>", 
               userdate($a->time));
    }

    print_table($table);
}


function survey_get_template_name($templateid) {
    global $DB;

    if ($templateid) {
        if ($ss = $DB->get_record("surveys", array("id"=>$templateid))) {
            return $ss->name;
        }
    } else {
        return "";
    }
}



function survey_shorten_name ($name, $numwords) {
    $words = explode(" ", $name);
    for ($i=0; $i < $numwords; $i++) {
        $output .= $words[$i]." ";
    }
    return $output;
}



function survey_print_multi($question) {
    global $USER, $DB, $qnum, $checklist, $DB;

    $stripreferthat = get_string("ipreferthat", "survey");
    $strifoundthat = get_string("ifoundthat", "survey");
    $strdefault    = get_string('default');
    $strresponses  = get_string('responses', 'survey');

    print_heading($question->text, null, 3, 'questiontext');
    echo "\n<table width=\"90%\" cellpadding=\"4\" cellspacing=\"1\" border=\"0\">";

    $options = explode( ",", $question->options);
    $numoptions = count($options);

    $oneanswer = ($question->type == 1 || $question->type == 2) ? true : false;
    if ($question->type == 2) {
        $P = "P";
    } else {
        $P = "";
    }

    echo "<tr class=\"smalltext\"><th scope=\"row\">$strresponses</th>";
    while (list ($key, $val) = each ($options)) {
        echo "<th scope=\"col\" class=\"hresponse\">$val</th>\n";
    }
    echo "<th>&nbsp;</th></tr>\n";

    if ($oneanswer) {
        echo "<tr><th scope=\"col\" colspan=\"6\">$question->intro</th></tr>\n";
    } else {
        echo "<tr><th scope=\"col\" colspan=\"7\">$question->intro</th></tr>\n"; 
    }

    $subquestions = $DB->get_records_list("survey_questions", "id", explode(',', $question->multi));

    foreach ($subquestions as $q) {
        $qnum++;
        $rowclass = survey_question_rowclass($qnum);
        if ($q->text) {
            $q->text = get_string($q->text, "survey");
        }

        echo "<tr class=\"$rowclass rblock\">";
        if ($oneanswer) {

            echo "<th scope=\"row\" class=\"optioncell\">";
            echo "<b class=\"qnumtopcell\">$qnum</b> &nbsp; ";
            echo $q->text ."</th>\n";
            for ($i=1;$i<=$numoptions;$i++) {
                $hiddentext = get_accesshide($options[$i-1]);
                $id = "q$P" . $q->id . "_$i";
                echo "<td><label for=\"$id\"><input type=\"radio\" name=\"q$P$q->id\" id=\"$id\" value=\"$i\" />$hiddentext</label></td>";
            }
            $default = get_accesshide($strdefault, 'label', '', "for=\"q$P$q->id\"");
            echo "<td class=\"whitecell\"><input type=\"radio\" name=\"q$P$q->id\" id=\"q$P" . $q->id . "_D\" value=\"0\" checked=\"checked\" />$default</td>";
            $checklist["q$P$q->id"] = $numoptions;
        
        } else { 
            // yu : fix for MDL-7501, possibly need to use user flag as this is quite ugly.
            echo "<th scope=\"row\" class=\"optioncell\">";
            echo "<b class=\"qnumtopcell\">$qnum</b> &nbsp; ";
            $qnum++;
            echo "<span class=\"preferthat smalltext\">$stripreferthat</span> &nbsp; ";
            echo "<span class=\"option\">$q->text</span></th>\n";
            for ($i=1;$i<=$numoptions;$i++) {
                $hiddentext = get_accesshide($options[$i-1]);
                $id = "qP" . $q->id . "_$i";
                echo "<td><label for=\"$id\"><input type=\"radio\" name=\"qP$q->id\" id=\"$id\" value=\"$i\" />$hiddentext</label></td>";
            }
            $default = get_accesshide($strdefault, 'label', '', "for=\"qP$q->id\"");
            echo "<td><input type=\"radio\" name=\"qP$q->id\" id=\"qP$q->id\" value=\"0\" checked=\"checked\" />$default</td>";
            echo "</tr>";

            echo "<tr class=\"$rowclass rblock\">";
            echo "<th scope=\"row\" class=\"optioncell\">";
            echo "<b class=\"qnumtopcell\">$qnum</b> &nbsp; ";
            echo "<span class=\"foundthat smalltext\">$strifoundthat</span> &nbsp; ";
            echo "<span class=\"option\">$q->text</span></th>\n";
            for ($i=1;$i<=$numoptions;$i++) {
                $hiddentext = get_accesshide($options[$i-1]);
                $id = "q" . $q->id . "_$i";
                echo "<td><label for=\"$id\"><input type=\"radio\" name=\"q$q->id\" id=\"$id\" value=\"$i\" />$hiddentext</label></td>";
            }
            $default = get_accesshide($strdefault, 'label', '', "for=\"q$q->id\"");
            echo "<td class=\"buttoncell\"><input type=\"radio\" name=\"q$q->id\" id=\"q$q->id\" value=\"0\" checked=\"checked\" />$default</td>";
            $checklist["qP$q->id"] = $numoptions;
            $checklist["q$q->id"] = $numoptions;            
        }
        echo "</tr>\n";
    }
    echo "</table>";
}



function survey_print_single($question) {
    global $DB, $qnum;

    $rowclass = survey_question_rowclass(0);

    $qnum++;

    echo "<br />\n";
    echo "<table width=\"90%\" cellpadding=\"4\" cellspacing=\"0\">\n";
    echo "<tr class=\"$rowclass\">";
    echo "<th scope=\"row\" class=\"optioncell\"><label for=\"q$question->id\"><b class=\"qnumtopcell\">$qnum</b> &nbsp; ";
    echo "<span class=\"questioncell\">$question->text</span></label></th>\n";
    echo "<td class=\"questioncell smalltext\">\n";


    if ($question->type == 0) {           // Plain text field
        echo "<textarea rows=\"3\" cols=\"30\" name=\"q$question->id\" id=\"q$question->id\">$question->options</textarea>";

    } else if ($question->type > 0) {     // Choose one of a number
        $strchoose = get_string("choose");
        echo "<select name=\"q$question->id\" id=\"q$question->id\">";
        echo "<option value=\"0\" selected=\"selected\">$strchoose...</option>";
        $options = explode( ",", $question->options);
        foreach ($options as $key => $val) {
            $key++;
            echo "<option value=\"$key\">$val</option>";
        }
        echo "</select>";

    } else if ($question->type < 0) {     // Choose several of a number
        $options = explode( ",", $question->options);
        notify("This question type not supported yet");
    }

    echo "</td></tr></table>";

}

function survey_question_rowclass($qnum) {

    if ($qnum) {
        return $qnum % 2 ? 'r0' : 'r1';
    } else {
        return 'r0';
    }
}

function survey_print_graph($url) {
    global $CFG, $SURVEY_GHEIGHT, $SURVEY_GWIDTH;

    if (empty($CFG->gdversion)) {
        echo "(".get_string("gdneed").")";

    } else {
        echo "<img class='resultgraph' height=\"$SURVEY_GHEIGHT\" width=\"$SURVEY_GWIDTH\"".
             " src=\"$CFG->wwwroot/mod/survey/graph.php?$url\" alt=\"".get_string("surveygraph", "survey")."\" />";
    }
}

function survey_get_view_actions() {
    return array('download','view all','view form','view graph','view report');
}

function survey_get_post_actions() {
    return array('submit');
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the survey.
 * @param $mform form passed by reference
 */
function survey_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'surveyheader', get_string('modulenameplural', 'survey'));
    $mform->addElement('checkbox', 'reset_survey_answers', get_string('deleteallanswers','survey'));
    $mform->addElement('checkbox', 'reset_survey_analysis', get_string('deleteanalysis','survey'));
    $mform->disabledIf('reset_survey_analysis', 'reset_survey_answers', 'checked');
}

/**
 * Course reset form defaults.
 */
function survey_reset_course_form_defaults($course) {
    return array('reset_survey_answers'=>1, 'reset_survey_analysis'=>1);
}

/**
 * Actual implementation of the rest coures functionality, delete all the
 * survey responses for course $data->courseid.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function survey_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'survey');
    $status = array();

    $surveyssql = "SELECT ch.id
                     FROM {survey} ch
                    WHERE ch.course=?";
    $params = array($data->courseid);

    if (!empty($data->reset_survey_answers)) {
        $DB->delete_records_select('survey_answers', "survey IN ($surveyssql)", $params);
        $DB->delete_records_select('survey_analysis', "survey IN ($surveyssql)", $params);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallanswers', 'survey'), 'error'=>false);
    }

    if (!empty($data->reset_survey_analysis)) {
        $DB->delete_records_select('survey_analysis', "survey IN ($surveyssql)", $params);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallanswers', 'survey'), 'error'=>false);
    }

    // no date shifting
    return $status;
}

/**
 * Returns all other caps used in module
 */
function survey_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function survey_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;

        default: return null;
    }
}