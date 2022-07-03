<?php

require(__DIR__.'/config.php');
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/externallib.php");
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

require_once("{$CFG->libdir}/completionlib.php");
require_once("{$CFG->libdir}/accesslib.php");
//$PAGE->set_pagelayout('frontpage');

//list courses
global $DB;
//$PAGE->set_pagetype('site-index');


$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$sort         = optional_param('sort', 'username', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
$ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
$lu           = optional_param('lu', '2', PARAM_INT);            // show local users
$acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
$unlock       = optional_param('unlock', 0, PARAM_INT);
$resendemail  = optional_param('resendemail', 0, PARAM_INT);
$context = context_system::instance();

$sitecontext = context_system::instance();
$site = get_site();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/task.php');




echo $OUTPUT->header();

// $courses = get_courses();
// $i=0;
// while($i<= count($courses)){
//     if($courses[$i]->format!= 'site'){

    
//     //    print_r($courses[$i]);
//     }
//     $i++;
// }
function enroll_user($userid, $course, $modifier) {                                                
    global $DB;                                                                                    
    $enrolData = $DB->get_record('enrol', array('enrol'=>'manual', 'courseid'=>$course));          
    $user_enrolment = new stdClass();                                                              
        $user_enrolment->enrolid = $enrolData->id;                                                 
        $user_enrolment->status = '0';                                                             
        $user_enrolment->userid = $userid;                                                         
        $user_enrolment->timestart = time();                                                       
        $user_enrolment->timeend =  '0';                                                           
        $user_enrolment->modifierid = $modifier;                                                   
        //Modifierid in this table is userid who enrolled this user manually
        $user_enrolment->timecreated = time();                                                     
        $user_enrolment->timemodified = time();                                                    
    $insertId = $DB->insert_record('user_enrolments', $user_enrolment);                            
    //addto log                                                                                    
    $context = $DB->get_record('context', array('contextlevel'=>50, 'instanceid'=>$course));          
    $role = new stdClass();                                                                        
        $role->roleid = 5;                                                                         
        $role->contextid = $context->id;                                                           
        $role->userid = $userid;                                                                   
        $role->component = '';                                                                     
        $role->itemid = 0;                                                                         
        $role->timemodified = time();                                                              
        $role->modifierid = $modifier;                                                             
    $insertId2 = $DB->insert_record('role_assignments', $role);                                    
    //add_to_log($course, '', $modifierid, 'automated');                
    return array('user_enrolment'=>$insertId, 'role_assignment'=>$insertId2);                      
}     

class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        global $DB;
        global $USER;
        global $OUTPUT;
        global $PAGE;
        $mform = $this->_form; // Don't forget the underscore! 

        // $mform->addElement('text', 'email', get_string('email')); // Add elements to your form
        // $mform->setType('email', PARAM_NOTAGS);                   //Set type of element
        // $mform->setDefault('email', '');        //Default value
        
        // $mform->addElement('submit', 'submit', get_string('submit')); // Add elements to your form
        // $mform->setType('submit', PARAM_NOTAGS);                   //Set type of element
        // $mform->setDefault('email', 'Enroll in course');        //Default value

        
//         $sqlquer="select id,name from mdl_course_categories";

//         $depdp=$DB->get_records_sql($sqlquer);
        
//         $deptlistt=array();
        
//         foreach($depdp as $depp)
        
//         {
        
//         $deptlist[$depp->id] = $depp->name;
        
//         }
        
//         $select2 = $mform->addElement('multi-select', 'name', 'Course Category', $deptlist);
        
// //        $select2->setMultiple(true);        





$courses = get_courses();

    	$courselist = array();

foreach ($courses as  $course) {
if($course->format != "site")
{
    $courselist[$course->id] = $course->fullname;

}

 }

          $options = array('multiple' => false,
                	'showsuggestions' => true,
                    'ajax'=>'courselist'

     );

         $mform->addElement('select', 'courselist', 'Courses', $courselist, $options);




    // create the user filter form
    $ufiltering = new user_filtering();
global $sort, $dir, $page, $perpage, $context;

    list($extrasql, $params) = $ufiltering->get_sql_filter();
    $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params, $context);
    $usercount = get_users(true);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

    $options = array('multiple' => true,
    'showsuggestions' => true,
    'ajax'=>'courselist'

);
//print_r($users);
//for the sake of converting STD class into associative array
//$users = json_decode(json_encode($users[2]), true);
$array = array();
foreach($users as $key){
    $array[$key->id] = $key->firstname. " ".$key->lastname;
    
}
//print_r($array);

$mform->addElement('select', 'userlist', 'Users', $array, $options);













        $this->add_action_buttons($cancel=false, $submitlabel="Enroll in courses");
        return $mform;
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}





    if ($extrasql !== '') {
//        echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
        $usercount = $usersearchcount;
    } else {
  //      echo $OUTPUT->heading("$usercount ".get_string('users'));
    }

    $strall = get_string('all');
//print_r($users);


    // Order in string will ensure that the name columns are in the correct order.
    $usernames = order_in_string($allusernamefields, $fullnamesetting);
//print_r($usernames);
    $fullnamedisplay = array();
    foreach ($usernames as $name) {
        // Use the link from $$column for sorting on the user's name.

        $fullnamedisplay[] = ${$name};

    }
    // All of the names are in one column. Put them into a string and separate them with a /.
    $fullnamedisplay = implode(' / ', $fullnamedisplay);
    // If $sort = name then it is the default for the setting and we should use the first name to sort by.
    if ($sort == "name") {
        // Use the first item in the array.
        $sort = reset($usernames);
    }

$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql, $params, $context);
    $usercount = get_users(false);
//    print_r($users);
    $usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);
//print_r($usercount);
$form = new simplehtml_form();
$form->display();
//print_r($_POST);
$context_course = get_context_instance(CONTEXT_COURSE, $_POST['courselist']);
//$context_mod = get_context_instance(CONTEXT_MODULE, $courseid);
//print_r($user_records);
if(isset($_POST['courselist']) && is_array($_POST['userlist'])){
    $user_records = get_enrolled_users($context_course, '', 0, '*');

//check if user is enrolled
for($i=0;$i<count($_POST['userlist']);$i++)
{

    $context = get_context_instance(CONTEXT_COURSE, $_POST['courselist'], MUST_EXIST);
    $enrolled = is_enrolled($context, $_POST['userlist'][$i], '', true);
    if(!$enrolled){
    //enroll the user
    
//    echo 'hii';
    $x = enroll_user($_POST['userlist'][0], $_POST['courselist'],0);
  //  print_r($x);
echo "User". $_POST['userlist'][0]." is now enrolled!";
    }

}
    
}
