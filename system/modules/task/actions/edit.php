<?php

//load form elements for required feilds
use \Html\Form\InputField as InputField;
use \Html\Form\Select as Select;
use \Html\Form\Autocomplete as Autocomplete;


function edit_GET($w) {
    $p = $w->pathMatch("id");
    $task = (!empty($p["id"]) ? $w->Task->getTask($p["id"]) : new Task($w));
    
    // Register for timelog
    $w->Timelog->registerTrackingObject($task);
    
    if (!empty($task->id) && !$task->canView($w->Auth->user())) {
        $w->error("You do not have permission to edit this Task", "/task/tasklist");
    }
	
    // Get a list of the taskgroups and filter by what can be used
    $taskgroups = array_filter($w->Task->getTaskGroups(), function($taskgroup){
        return $taskgroup->getCanICreate();
    });
    
    $tasktypes = array();
    $priority = array();
    $members = array();
    
    // Try and prefetch the taskgroup by given id
    $taskgroup = null;
    $taskgroup_id = $w->request("gid");
    $assigned = 0;
    if (!empty($taskgroup_id) || !empty($task->task_group_id)) {
        $taskgroup = $w->Task->getTaskGroup(!empty($task->task_group_id) ? $task->task_group_id : $taskgroup_id);
        
        if (!empty($taskgroup->id)) {
            $tasktypes = $w->Task->getTaskTypes($taskgroup->task_group_type);
            $priority = $w->Task->getTaskPriority($taskgroup->task_group_type);
            $members = $w->Task->getMembersBeAssigned($taskgroup->id);
            sort($members);
            array_unshift($members,array("Unassigned","unassigned"));
            $assigned = (empty($task->assignee_id)) ? "unassigned" : $task->assignee_id;
        }
    }
    
    // Create form
    $form = array(
        (!empty($p["id"]) ? 'Edit task' : "Create a new task") => array(
            array(
                (new Autocomplete())
                    ->setLabel("Task Group <small>Required</small>")
                    ->setName(!empty($p["id"]) ? "task_group_id_text" : "task_group_id")
                    ->setReadOnly(!empty($p["id"]) ? 'true' : null)
                    ->setOptions($taskgroups)
                    ->setValue(!empty($taskgroup) ? $taskgroup->id : null)
                    ->setTitle(!empty($taskgroup) ? $taskgroup->getSelectOptionTitle(): null)
                    ->setRequired('required'),
//				!empty($p["id"]) ?
//                        array("Task Group", "text", "-task_group_id_text", $taskgroup->title) :
//                        array("Task Group", "autocomplete", "task_group_id", !empty($task->task_group_id) ? $task->task_group_id : $taskgroup_id, $taskgroups),
                (new Select([
					"id|name" => "task_type"
				]))->setLabel("Task Type <small>Required</small>")
                    ->setDisabled(!empty($p["id"]) ? "true" : null)
                    ->setOptions($tasktypes)
                    ->setSelectedOption(!empty($p["id"]) ? $task->task_type : sizeof($tasktypes) === 1 ? $tasktypes[0] : null)
                    ->setRequired('required')
//                !empty($p["id"]) ?
//                        array("Task Type", "select", "-task_type", $task->task_type, $tasktypes) :
//                        //array("Task Type", "select", "task_type", $task->task_type, $tasktypes)
//                        array("Task Type", "select", "task_type", (sizeof($tasktypes) === 1) ? $tasktypes[0] : null, $tasktypes)
            ),
            array(
                array("Task Title", "text", "title", $task->title),
                array("Status", "select", "status", $task->status, $task->getTaskGroupStatus()),
            ),
            array(
                array("Priority", "select", "priority", $task->priority, $priority),
                array("Date Due", "date", "dt_due", formatDate($task->dt_due)),
                !empty($taskgroup) && $taskgroup->getCanIAssign() ?
                	array("Assigned To", "select", "assignee_id", $assigned, $members) :
                	array("Assigned To", "select", "-assignee_id", $assigned, $members)
            ),
			array(
				array("Estimated hours", "text", "estimate_hours", $task->estimate_hours),
				array("Effort", "text", "effort", $task->effort),
                            (new InputField())->setName('rate')->setLabel('Rate')->setValue($task->rate)->setPattern('^\d+(?:\.\d{1,2})?$')
			),
            array(array("Description", "textarea", "description", $task->description)),
        	!empty($p['id']) ? [["Task Group ID", "hidden", "task_group_id", $task->task_group_id]] : null
        )
    );
	
//	if (!empty($p['id'])) {
//		$form['Edit task [' . $task->id . ']'][5][] = array("Task Group ID", "hidden", "task_group_id", $task->task_group_id);
//	}

    if (empty($p['id'])) {
    	History::add("New Task");
    } else {
    	History::add("Task: {$task->title}", null, $task);
    }
    $w->ctx("task", $task);
    $w->ctx("form", Html::multiColForm($form, $w->localUrl("/task/edit/{$task->id}"), "POST", "Save", "edit_form", "prompt", null, "_self", true, Task::$_validation));
   
    //////////////////////////
    // Build time log table //
    //////////////////////////

//    $timelog = $task->getTimeLog();
//    $total_seconds = 0;
//    
//    $table_header = array("Assignee", "Start", "Period (hours)", "Comment","Actions");
//    $table_data = array();
//    if (!empty($timelog)) {
//        // for each entry display, calculate period and display total time on task
//        foreach ($timelog as $log) {
//            // get time difference, start to end
//            $seconds = $log->dt_end - $log->dt_start;
//            $period = $w->Task->getFormatPeriod($seconds);
//			$comment = $w->Comment->getComment($log->comment_id);
//			$comment = !empty($comment) ? $comment->comment : "";
//            $table_row = array(
//                $w->Task->getUserById($log->user_id),
//                formatDateTime($log->dt_start),
//                $period,
//            	!empty($comment) ? $w->Comment->renderComment($comment) : "",
//            );
//            
//            // Build list of buttons
//            $buttons = '';
//            if ($log->is_suspect == "0") {
//                $total_seconds += $seconds;
//                $buttons .= Html::box($w->localUrl("/task/addtime/".$task->id."/".$log->id)," Edit ",true);
//            }
//
//            if ($w->Task->getIsOwner($task->task_group_id, $w->Auth->user()->id)) {
//                $buttons .= Html::b($w->localUrl("/task/suspecttime/".$task->id."/".$log->id), ((empty($log->is_suspect) || $log->is_suspect == "0") ? "Review" : "Accept"));
//            }
//            
//            $buttons .= Html::b($w->localUrl("/task/deletetime/".$task->id."/".$log->id), "Delete", "Are you sure you wish to DELETE this Time Log Entry?");
//            
//            $table_row[] = $buttons;
//            
//            $table_data[] = $table_row;
//        }
//        $table_data[] = array("<b>Total</b>", "","<b>".$w->Task->getFormatPeriod($total_seconds)."</b>","","");
//    }
//    // display the task time log
//    $w->ctx("timelog",Html::table($table_data, null, "tablesorter", $table_header));
    
    ///////////////////
    // Notifications //
    ///////////////////
    
    $notify = null;
    // If I am assignee, creator or task group owner, I can get notifications for this task
    if (!empty($task->id) && $task->getCanINotify()) {

        // get User set notifications for this Task
        $notify = $w->Task->getTaskUserNotify($w->Auth->user()->id, $task->id);
        if (empty($notify)) {
            $logged_in_user_id = $w->Auth->user()->id;
            // Get my role in this task group
            $me = $w->Task->getMemberGroupById($task->task_group_id, $logged_in_user_id);
            
            $type = "";
            if ($task->assignee_id == $logged_in_user_id) {
                $type = "assignee";
            } else if ($task->getTaskCreatorId() == $logged_in_user_id) {
                $type = "creator";
            } else if ($w->Task->getIsOwner($task->task_group_id, $logged_in_user_id)) {
                $type = "other";
            }

            if (!empty($type) && !empty($me)) {
                $notify = $w->Task->getTaskGroupUserNotifyType($logged_in_user_id, $task->task_group_id, strtolower($me->role), $type);
            }
        }

        // create form. if still no 'notify' all boxes are unchecked
        $form = array(
            "Notification Events" => array(
                array(array("","hidden","task_creation", "0")),
                array(
                    array("Task Details Update","checkbox","task_details", !empty($notify->task_details) ? $notify->task_details : null),
                    array("Comments Added","checkbox","task_comments", !empty($notify->task_comments) ? $notify->task_comments : null)
                ),
                array(
                    array("Time Log Entry","checkbox","time_log", !empty($notify->time_log) ? $notify->time_log : null),
                    array("Task Data Updated","checkbox","task_data", !empty($notify->task_data) ? $notify->task_data : null)
                ),
                array(array("Documents Added","checkbox","task_documents", !empty($notify->task_documents) ? $notify->task_documents : null))
            )
        );

        $w->ctx("tasknotify", Html::multiColForm($form, $w->localUrl("/task/updateusertasknotify/".$task->id),"POST"));
    }
}

function edit_POST($w) {
    $p = $w->pathMatch("id");
    $task = (!empty($p["id"]) ? $w->Task->getTask($p["id"]) : new Task($w));
    $taskdata = null;
    if (!empty($p["id"])) {
        $taskdata = $w->Task->getTaskData($p['id']);
    }
    
    $task->fill($_POST['edit']);
    $task->rate = abs($task->rate) == 0 ? NULL : $task->rate;
    $task->assignee_id = intval($_POST['edit']['assignee_id']);
    if (empty($task->dt_due)) {
        $task->dt_due = $w->Task->getNextMonth();
    }
    
    $task->insertOrUpdate(true);
    
    // Tell the template what the task id is (this post action is being called via ajax)
    $w->setLayout(null);
    $w->out($task->id);
    
    // Get existing task_data objects for this task and update them
    $existing_task_data = $w->Task->getTaskData($task->id);
    if (!empty($existing_task_data)) {
        foreach($existing_task_data as $e_task_data) {
			// Autocomplete fields
			if (strpos($e_task_data->data_key, \Html\Form\Autocomplete::$_prefix) === 0) {
				$e_task_data->delete();
				continue;
			}
			
            foreach($_POST["extra"] as $key => $data) {
                if ($key == \CSRF::getTokenId()) {
                    unset($_POST["extra"][\CSRF::getTokenID()]);
                    continue;
                }
            	
                if ($e_task_data->data_key == $key) {
                    $e_task_data->value = $data;
                    $e_task_data->update();
                    
                    unset($_POST["extra"][$key]);
                    continue;
                }
                
                // If we get here then remove the existing data?
                // $e_task_data->delete();
            }
        }
    }
    
    // Insert data that didn't exist above as new task_data objects
    if (!empty($_POST["extra"])) {
        foreach ($_POST["extra"] as $key => $data) {
			if (strpos($key, \Html\Form\Autocomplete::$_prefix) !== 0) {
				$tdata = new TaskData($w);
				$tdata->task_id = $task->id;
				$tdata->data_key = $key;
				$tdata->value = $data;
				$tdata->insert();
			}
        }
    }
    
	
	if (empty($p['id']) && Config::get('task.ical.send') == true) {		
        $data = $task->getIcal();
        $user = $w->Auth->getUser($task->assignee_id);
        $contact = !empty($user->id) ? $user->getContact() : $w->Auth->user()->getContact();

        $messageObject = Swift_Message::newInstance();
		$messageObject->setTo(array($contact->email));
        $messageObject->setSubject("Invite to: " . $task->title)
            ->setFrom($w->Auth->user()->getContact()->email);

        $messageObject->addPart("Your iCal is attached<br/><br/><a href='http://www.google.com/calendar/event?
action=TEMPLATE&text={$task->title}
&dates=" . date("Ymd", strtotime(str_replace('/', '-', $task->dt_due))) . "/" . date("Ymd", strtotime(str_replace('/', '-', $task->dt_due))) .
"&details=" . htmlentities($task->description) .
"&trp=false target='_blank' rel='nofollow'>Add to Google calendar</a><br/><br/>View the Task at: " . $task->toLink(null, null, $user), "text/html");

        $ics_content = $data;
		$messageObject->addPart($ics_content, "text/calendar");
		
		file_put_contents(FILE_ROOT . "invite.ics", $data);
		
        $ics_attachment = Swift_Attachment::newInstance()
                ->setBody(trim($ics_content), "application/ics; name=\"invite.ics\"")
                ->setEncoder(Swift_Encoding::get7BitEncoding());
        $headers = $ics_attachment->getHeaders();
        $content_type_header = $headers->get("Content-Type");
        $content_type_header->setValue("application/ics; name=\"invite.ics\"");
        $content_type_header->setParameters(array(
            'charset' => 'UTF-8',
            'method' => 'REQUEST'
        ));
		
		$content_disposition_header = $headers->get("Content-Disposition");
		$content_disposition_header->setValue("attachment; filename=\"invite.ics\"");
		
		$messageObject->attach($ics_attachment);

		$email_layer = Config::get('email.layer');
		$swiftmailer_transport = new SwiftMailerTransport($w, $email_layer);
        $mailObject = Swift_Mailer::newInstance($swiftmailer_transport->getTransport($email_layer));
        $mailObject->send($messageObject);

		unlink(FILE_ROOT . "invite.ics");
    }
}
