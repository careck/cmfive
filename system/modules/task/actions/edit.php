<?php

function edit_GET($w) {
    $p = $w->pathMatch("id");
    $task = (!empty($p["id"]) ? $w->Task->getTask($p["id"]) : new Task($w));

    if (!empty($task->id) && !$task->canView($w->Auth->user())) {
        $w->error("You do not have permission to edit this Task", "/task/tasklist");
    }

    // Get a list of the taskgroups and filter by what can be used
    $taskgroups = array_filter($w->Task->getTaskGroups(), function($taskgroup) {
        return $taskgroup->getCanICreate();
    });

    $tasktypes = array();
    $priority = array();
    $members = array();

    // Try and prefetch the taskgroup by given id
    $taskgroup = null;
    $taskgroup_id = $w->request("gid");
    if (!empty($taskgroup_id) || !empty($task->task_group_id)) {
        $taskgroup = $w->Task->getTaskGroup(!empty($task->task_group_id) ? $task->task_group_id : $taskgroup_id);

        if (!empty($taskgroup->id)) {
            $tasktypes = $w->Task->getTaskTypes($taskgroup->task_group_type);
            $priority = $w->Task->getTaskPriority($taskgroup->task_group_type);
            $members = $w->Task->getMembersBeAssigned($taskgroup->id);
            sort($members);
        }
    }

    // Create form
    $form = array(
        (!empty($p["id"]) ? "Edit task" : "Create a new task") => array(
            array(
                !empty($p["id"]) ?
                        array("Task Group", "text", "-task_group_id_text", $taskgroup->title) :
                        array("Task Group", "autocomplete", "task_group_id", !empty($task->task_group_id) ? $task->task_group_id : $taskgroup_id, $taskgroups),
                !empty($p["id"]) ?
                        array("Task Type", "select", "-task_type", $task->task_type, $tasktypes) :
                        array("Task Type", "select", "task_type", $task->task_type, $tasktypes)
            ),
            array(
                array("Task Title", "text", "title", $task->title),
                array("Status", "select", "status", $task->status, $task->getTaskGroupStatus()),
            ),
            array(
                array("Priority", "select", "priority", $task->priority, $priority),
                array("Date Due", "date", "dt_due", formatDate($task->dt_due)),
                !empty($taskgroup) && $taskgroup->getCanIAssign() ?
                        array("Assigned To", "select", "assignee_id", $task->assignee_id, $members) :
                        array("Assigned To", "select", "-assignee_id", $task->assignee_id, $members)
            ),
            array(array("Description", "textarea", "description", $task->description)),
        )
    );

    if (empty($p['id'])) {
        History::add("New Task");
    } else {
        History::add("Task: {$task->title}");
    }
    $w->ctx("task", $task);
    $w->ctx("form", Html::multiColForm($form, $w->localUrl("/task/edit/{$task->id}"), "POST", "Save", "edit_form"));

    //////////////////////////
    // Build time log table //
    //////////////////////////

    $timelog = $task->getTimeLog();
    $total_seconds = 0;

    $table_header = array("Assignee", "Start", "Period (hours)", "Comment", "Actions");
    $table_data = array();
    if (!empty($timelog)) {
        // for each entry display, calculate period and display total time on task
        foreach ($timelog as $log) {
            // get time difference, start to end
            $seconds = $log->dt_end - $log->dt_start;
            $period = $w->Task->getFormatPeriod($seconds);
            $comment = $w->Comment->getComment($log->comment_id);
            $comment = !empty($comment) ? $comment->comment : "";
            $table_row = array(
                $w->Task->getUserById($log->user_id),
                formatDateTime($log->dt_start),
                $period,
                !empty($comment) ? $w->Comment->renderComment($comment) : "",
            );

            // Build list of buttons
            $buttons = '';
            if ($log->is_suspect == "0") {
                $total_seconds += $seconds;
                $buttons .= Html::box($w->localUrl("/task/addtime/" . $task->id . "/" . $log->id), " Edit ", true);
            }

            if ($w->Task->getIsOwner($task->task_group_id, $w->Auth->user()->id)) {
                $buttons .= Html::b($w->localUrl("/task/suspecttime/" . $task->id . "/" . $log->id), ((empty($log->is_suspect) || $log->is_suspect == "0") ? "Review" : "Accept"));
            }

            $buttons .= Html::b($w->localUrl("/task/deletetime/" . $task->id . "/" . $log->id), "Delete", "Are you sure you wish to DELETE this Time Log Entry?");

            $table_row[] = $buttons;

            $table_data[] = $table_row;
        }
        $table_data[] = array("<b>Total</b>", "", "<b>" . $w->Task->getFormatPeriod($total_seconds) . "</b>", "", "");
    }
    // display the task time log
    $w->ctx("timelog", Html::table($table_data, null, "tablesorter", $table_header));

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
                array(array("", "hidden", "task_creation", "0")),
                array(
                    array("Task Details Update", "checkbox", "task_details", !empty($notify->task_details) ? $notify->task_details : null),
                    array("Comments Added", "checkbox", "task_comments", !empty($notify->task_comments) ? $notify->task_comments : null)
                ),
                array(
                    array("Time Log Entry", "checkbox", "time_log", !empty($notify->time_log) ? $notify->time_log : null),
                    array("Task Data Updated", "checkbox", "task_data", !empty($notify->task_data) ? $notify->task_data : null)
                ),
                array(array("Documents Added", "checkbox", "task_documents", !empty($notify->task_documents) ? $notify->task_documents : null))
            )
        );

        $w->ctx("tasknotify", Html::multiColForm($form, $w->localUrl("/task/updateusertasknotify/" . $task->id), "POST"));
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
    $task->assignee_id = intval($_POST['edit']['assignee_id']);
    if (empty($task->dt_due)) {
        $task->dt_due = $w->Task->getNextMonth();
    }

    $task->insertOrUpdate();

    // Tell the template what the task id is (this post action is being called via ajax)
    $w->setLayout(null);
    $w->out($task->id);

    // Get existing task_data objects for this task and update them
    $existing_task_data = $w->Task->getTaskData($task->id);
    if (!empty($existing_task_data)) {
        foreach ($existing_task_data as $e_task_data) {
            foreach ($_POST["extra"] as $key => $data) {
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
            $tdata = new TaskData($w);
            $tdata->task_id = $task->id;
            $tdata->data_key = $key;
            $tdata->value = $data;
            $tdata->insert();
        }
    }

    // Send email with iCal when new task (?)
    if (empty($p['id']) && Config::get('task.ical.send') == true) {
        $data = $task->getIcal();
//        $ical = Swift_Attachment::newInstance($data, "calendar.ics", 'text/calendar');
        $user = $w->Auth->getUser($task->assignee_id);
        $contact = $user->getContact();

        $messageObject = Swift_Message::newInstance();
//        $messageObject->setContentType("multipart/alternative");
        $messageObject->addPart("Your iCal is attached<br/>View Task at: " . $task->toLink(null, null, $user), "text/html");

        $messageObject->setSubject("Invite to: " . $task->title)
                ->setFrom($w->Auth->user()->getContact()->email);

        $messageObject->setTo(array($contact->email));
        $ics_content = $data;
        $ics_attachment = Swift_Attachment::newInstance()
                ->setBody(trim($ics_content))
                ->setEncoder(Swift_Encoding::get7BitEncoding());
        $headers = $ics_attachment->getHeaders();
        $content_type_header = $headers->get("Content-Type");
        $content_type_header->setValue("text/calendar");
        $content_type_header->setParameters(array(
            'charset' => 'UTF-8',
            'method' => 'REQUEST'
        ));
        $messageObject->attach($ics_attachment);

        $mailObject = Swift_Mailer::newInstance($w->Mail->getTransport());
        $mailObject->send($messageObject);

//        $w->Mail->sendMail($contact->email, $contact->email, "Invite to " . $task->title, 'Your iCal is attached<br/>View Task at: ' . $task->toLink(null, null, $user), null, null, [$ical], 'US-ASCII');
    }
}
