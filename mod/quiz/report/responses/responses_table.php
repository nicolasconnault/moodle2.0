<?php  // $Id$

class quiz_report_responses_table extends table_sql {

    var $useridfield = 'userid';

    var $reporturl;
    var $displayoptions;

    function quiz_report_responses_table($quiz , $qmsubselect, $groupstudents,
                $students, $questions, $candelete, $reporturl, $displayoptions){
        parent::table_sql('mod-quiz-report-responses-report');
        $this->quiz = $quiz;
        $this->qmsubselect = $qmsubselect;
        $this->groupstudents = $groupstudents;
        $this->students = $students;
        $this->questions = $questions;
        $this->candelete = $candelete;
        $this->reporturl = $reporturl;
        $this->displayoptions = $displayoptions;
    }
    function build_table(){
        global $CFG, $DB;
        if ($this->rawdata) {
            // Define some things we need later to process raw data from db.
            $this->strtimeformat = get_string('strftimedatetime');
            parent::build_table();
            //end of adding data from attempts data to table / download
            //now add averages at bottom of table :
            $params = array($this->quiz->id);

            $this->add_separator();
            if ($this->is_downloading()){
                $namekey = 'lastname';
            } else {
                $namekey = 'fullname';
            }
        }
    }

    function wrap_html_start(){
        if (!$this->is_downloading()) {
            if ($this->candelete) {
                // Start form
                $strreallydel  = addslashes_js(get_string('deleteattemptcheck','quiz'));
                echo '<div id="tablecontainer">';
                echo '<form id="attemptsform" method="post" action="' . $this->reporturl->out(true) .
                        '" onsubmit="confirm(\''.$strreallydel.'\');">';
                echo '<div style="display: none;">';
                echo $this->reporturl->hidden_params_out(array(), 0, $this->displayoptions);
                echo '</div>';
                echo '<div>';
            }
        }
    }
    function wrap_html_finish(){
        if (!$this->is_downloading()) {
            // Print "Select all" etc.
            if ($this->candelete) {
                echo '<table id="commands">';
                echo '<tr><td>';
                echo '<a href="javascript:select_all_in(\'DIV\',null,\'tablecontainer\');">'.
                        get_string('selectall', 'quiz').'</a> / ';
                echo '<a href="javascript:deselect_all_in(\'DIV\',null,\'tablecontainer\');">'.
                        get_string('selectnone', 'quiz').'</a> ';
                echo '&nbsp;&nbsp;';
                echo '<input type="submit" value="'.get_string('deleteselected', 'quiz_responses').'"/>';
                echo '</td></tr></table>';
                // Close form
                echo '</div>';
                echo '</form></div>';
            }
        }
    }


    function col_checkbox($attempt){
        if ($attempt->attempt){
            return '<input type="checkbox" name="attemptid[]" value="'.$attempt->attempt.'" />';
        } else {
            return '';
        }
    }

    function col_picture($attempt){
        global $COURSE;
        $user = new object();
        $user->id = $attempt->userid;
        $user->lastname = $attempt->lastname;
        $user->firstname = $attempt->firstname;
        $user->imagealt = $attempt->imagealt;
        $user->picture = $attempt->picture;
        return print_user_picture($user, $COURSE->id, $attempt->picture, false, true);
    }


    function col_timestart($attempt){
        if ($attempt->attempt) {
            $startdate = userdate($attempt->timestart, $this->strtimeformat);
            if (!$this->is_downloading()) {
                return  '<a href="review.php?q='.$this->quiz->id.'&amp;attempt='.$attempt->attempt.'">'.$startdate.'</a>';
            } else {
                return  $startdate;
            }
        } else {
            return  '-';
        }
    }
    function col_timefinish($attempt){
        if ($attempt->attempt) {
            if ($attempt->timefinish) {
                $timefinish = userdate($attempt->timefinish, $this->strtimeformat);
                if (!$this->is_downloading()) {
                    return '<a href="review.php?q='.$this->quiz->id.'&amp;attempt='.$attempt->attempt.'">'.$timefinish.'</a>';
                } else {
                    return $timefinish;
                }
            } else {
                return  '-';
            }
        } else {
            return  '-';
        }
    }

    function col_duration($attempt){
        if ($attempt->timefinish) {
            return format_time($attempt->duration);
        } elseif ($attempt->timestart) {
            return get_string('unfinished', 'quiz');
        } else {
            return '-';
        }
    }
    function col_sumgrades($attempt){
        if ($attempt->timefinish) {
            $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz);
            if (!$this->is_downloading()) {
                $gradehtml = '<a href="review.php?q='.$this->quiz->id.'&amp;attempt='.$attempt->attempt.'">'.$grade.'</a>';
                if ($this->qmsubselect && $attempt->gradedattempt){
                    $gradehtml = '<div class="highlight">'.$gradehtml.'</div>';
                }
                return $gradehtml;
            } else {
                return $grade;
            }
        } else {
            return '-';
        }
    }
    function other_cols($colname, $attempt){
        static $gradedstatesbyattempt = null, $states =array();
        if ($gradedstatesbyattempt === null){
            //get all the attempt ids we want to display on this page
            //or to export for download.
            $attemptids = array();
            foreach ($this->rawdata as $attempt){
                if ($attempt->attemptuniqueid > 0){  
                    $attemptids[] = $attempt->attemptuniqueid;
                    $states[$attempt->attemptuniqueid] = get_question_states($this->questions, $this->quiz, $attempt);
                }
            }
            $gradedstatesbyattempt = quiz_get_newgraded_states($attemptids, true, 'qs.id, qs.grade, qs.event, qs.question, qs.attempt');
        }
        if (preg_match('/^qsanswer([0-9]+)$/', $colname, $matches)){
            $questionid = $matches[1];
            $question = $this->questions[$questionid];
            $stateforqinattempt = $gradedstatesbyattempt[$attempt->attemptuniqueid][$questionid];
            $responses =  get_question_actual_response($question, $states[$attempt->attemptuniqueid][$questionid]);
            $response = (!empty($responses)? implode(', ',$responses) : '-');
            $grade = $stateforqinattempt->grade;
            if (!$this->is_downloading()) {
                $format_options = new stdClass;
                $format_options->para = false;
                $format_options->noclean = true;
                $format_options->newlines = false;
                if ($grade<= 0) {
                    $qclass = 'uncorrect';
                } elseif ($grade == 1) {
                    $qclass = 'correct';
                } else {
                    $qclass = 'partialcorrect';
                }          
                return '<span class="'.$qclass.'">'.format_text($response, FORMAT_MOODLE, $format_options).'</span>';
            } else {
                return format_text($response, FORMAT_MOODLE, $format_options);
            }
        } else {
            return NULL;
        }
    }

    function col_feedbacktext($attempt){
        if ($attempt->timefinish) {
            if (!$this->is_downloading()) {
                return quiz_report_feedback_for_grade(quiz_rescale_grade($attempt->sumgrades, $this->quiz), $this->quiz->id);
            } else {
                return strip_tags(quiz_report_feedback_for_grade(quiz_rescale_grade($attempt->sumgrades, $this->quiz), $this->quiz->id));
            }
        } else {
            return '-';
        }

    }

    function query_db($pagesize, $useinitialsbar=true){
        // Add table joins so we can sort by question answer
        // unfortunately can't join all tables necessary to fetch all answers
        // to get the state for one question per attempt row we must join two tables
        // and there is a limit to how many joins you can have in one query. In MySQL it
        // is 61. This means that when having more than 29 questions the query will fail.
        // So we join just the tables needed to sort the attempts.
        if($sort = $this->get_sql_sort()) {
                $this->sql->from .= ' ';
                $sortparts    = explode(',', $sort);
                $matches = array();
                foreach($sortparts as $sortpart) {
                    $sortpart = trim($sortpart);
                    if (preg_match('/^qsanswer([0-9]+)/', $sortpart, $matches)){
                        $qid = intval($matches[1]);
                        $this->sql->fields .=  ", qs$qid.grade AS qsgrade$qid, qs$qid.answer AS qsanswer$qid, qs$qid.event AS qsevent$qid, qs$qid.id AS qsid$qid";
                        $this->sql->from .= "LEFT JOIN {question_sessions} qns$qid ON qns$qid.attemptid = qa.uniqueid AND qns$qid.questionid = :qid$qid ";
                        $this->sql->from .=  "LEFT JOIN  {question_states} qs$qid ON qs$qid.id = qns$qid.newgraded ";
                        $this->sql->params['qid'.$qid] = $qid;
                    }
                }
        }
        parent::query_db($pagesize, $useinitialsbar);
    }
}
?>