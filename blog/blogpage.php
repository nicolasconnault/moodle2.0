<?php  // $Id: blogpage.php,v 1.25 2009/05/06 09:02:49 tjhunt Exp $

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
* Definition of blog page type.
 */
define('PAGE_BLOG_VIEW', 'blog-view');

// Blog class derived from moodle's page class
class page_blog extends page_base {

    var $editing = false;
    var $filtertype = NULL;
    var $filterselect = NULL;
    var $tagid = NULL;

    // Do any validation of the officially recognized bits of the data and forward to parent.
    // Do NOT load up "expensive" resouces (e.g. SQL data) here!
    function init_quick($data) {
        parent::init_quick($data);
        if (empty($data->pageid)) {
            //if no pageid then the user is viewing a collection of blog entries
            $this->id = 0; //set blog id to 0
        }
    }

    /**
     * Here you should load up all heavy-duty data for your page. Basically everything that
     * does not NEED to be loaded for the class to make basic decisions should NOT be loaded
     * in init_quick() and instead deferred here. Of course this function had better recognize
     * $this->full_init_done to prevent wasteful multiple-time data retrieval.
     */
    function init_full() {
        global $DB;

        if ($this->full_init_done) {
            return;
        }
        // I need to determine how best to utilize this function. Most init
        // is already done before we get here in blogFilter and blogInfo

        if ($this->courseid == 0 || $this->courseid == 1 || !is_numeric($this->courseid) ) {
            $this->courseid = '';
        }
        $this->full_init_done = true;
    }

    //over-ride parent method's print_header because blog already passes more than just the title along
    function print_header($pageTitle='', $pageHeading='', $pageNavigation='', $pageFocus='', $pageMeta='') {
        global $CFG, $USER;

        $this->init_full();
        $extraheader = '';
        if (!empty($USER) && !empty($USER->id) && $this->user_allowed_editing()) {
            if ($this->user_is_editing()) {
                $editingString = get_string('turneditingoff');
            } else {
                $editingString = get_string('turneditingon');
            }

            $params = $this->url->params();
            $params['edit'] = $this->user_is_editing() ? 0 : 1;
            $paramstring = '';
            foreach ($params as $key=>$val) {
                $paramstring .= '<input type="hidden" name="'.$key.'" value="'.s($val).'" />';
            }

            $extraheader = '<form '.$CFG->frametarget.' method="get" action="'.$this->url->out(false).'"><div>'
                             .$paramstring.'<input type="submit" value="'.$editingString.'" /></div></form>';
        }
        print_header($pageTitle, $pageHeading, $pageNavigation, $pageFocus, $pageMeta, true, $extraheader );
    }
}
?>
