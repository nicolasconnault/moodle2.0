<?php // $Id: index.php,v 2.9 2008/06/20 10:48:32 ethem Exp $

/// Load libraries
    require_once('../../config.php');
    require_once('const.php');
    require_once('locallib.php');
    require_once('localfuncs.php');
    require_once('authorizenet.class.php');

/// Parameters
    $orderid  = optional_param('order', 0, PARAM_INT);
    $courseid = optional_param('course', SITEID, PARAM_INT);
    $userid   = optional_param('user', 0, PARAM_INT);

/// Get course
    if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid', '', '', $courseid);
    }

/// Only SITE users can access to this page
    require_login(); // Don't use $courseid! User may want to see old orders.
    if (has_capability('moodle/legacy:guest', get_context_instance(CONTEXT_SYSTEM), $USER->id, false)) {
        print_error('noguest');
    }

/// Load strings. All strings should be defined here. locallib.php uses these strings.
    $strs = get_strings(array('search','status','action','time','course','confirm','yes','no','cancel','all','none','error'));
    $authstrs = get_strings(array('orderid','nameoncard','echeckfirslasttname','void','capture','refund','delete',
        'allpendingorders','authcaptured','authorizedpendingcapture','capturedpendingsettle','settled',
        'refunded','cancelled','expired','underreview','approvedreview','reviewfailed','tested','new',
        'paymentmethod','methodcc','methodecheck', 'paymentmanagement', 'orderdetails', 'cclastfour', 'isbusinesschecking','shopper',
        'transid','settlementdate','notsettled','amount','unenrolstudent'), 'enrol_authorize');

/// User wants to see all orders
    if (empty($orderid)) {
        authorize_print_orders($courseid, $userid);
    }
    else {
        authorize_print_order($orderid);
    }
?>
