<?php // $Id: events.php,v 1.11 2008/07/31 08:01:49 moodler Exp $

///////////////////////////////////////////////////////////////////////////
// Defines core event handlers                                           //
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////



/* List of handlers */

$handlers = array (

/* Messaging required parameters (object):
 *  modulename     - 
 *  userfrom
 *  userto
 *  subject
 *  fullmessage - the full message in a given format
 *  fullmessageformat  - the format if the full message (FORMAT_MOODLE, FORMAT_HTML, ..)
 *  fullmessagehtml  - the full version (the message processor will choose with one to use) 
 *  smallmessage - the small version of the message
 */

    'message_send' => array (
         'handlerfile'      => '/lib/messagelib.php',
         'handlerfunction'  => 'message_send_handler', 
         'schedule'         => 'instant'
     ),

/*
 * portfolio queued event - for non interactive file transfers
*/
    'portfolio_send' => array (
        'handlerfile'      => '/lib/portfolio.php',
        'handlerfunction'  => 'portfolio_handle_event',    // argument to call_user_func(), could be an array
        'schedule'         => 'cron'
    ),


/* more go here */
);




/* List of events thrown from Moodle core

==== user related events ====

user_created - object user table record
user_updated - object user table record
user_deleted - object user table record

==== course related events ====

course_category_updated - object course_categories table record
course_category_created - object course_categories table record
course_category_deleted - object course_categories table record

course_created - object course table record
course_updated - object course table record
course_deleted - object course table record

==== group related events ====

groups_group_created - object groups_group table record
groups_group_updated - object groups_group table record
groups_group_deleted - object groups_group table record

groups_member_added   - object userid, groupid properties
groups_member_removed - object userid, groupid properties

groups_grouping_created - object groups_grouping table record
groups_grouping_updated - object groups_grouping table record
groups_grouping_deleted - object groups_grouping table record

groups_members_removed          - object courseid+userid - removed all users (or one user) from all groups in course
groups_groupings_groups_removed - int course id - removed all groups from all groupings in course
groups_groups_deleted           - int course id - deleted all course groups
groups_groupings_deleted        - int course id - deleted all course groupings

==== role related evetns ====

role_assigned         - object role_assignments table record
role_unassigned       - object role_assignments table record

*/

?>
