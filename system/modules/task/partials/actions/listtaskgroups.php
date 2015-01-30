<?php 

function listtaskgroups_ALL(Web $w, $params = array()) {
    $taskgroups = $params['taskgroups'];
    
    $should_filter = !empty($params['should_filter']) ? $params['should_filter'] : false;
    
    if ($should_filter) {
        $taskgroups = array_filter($taskgroups, function($taskgroup) use ($w) {
            // First check if there are tasks
            $tasks = $taskgroup->getTasks();
            if (count($tasks) == 0) {
                return false;
            } else {
                // Check if any of the tasks are accessible to the user
                $tasks = array_filter($tasks, function($task) use ($w) {
                    return $task->getCanIView();
                });

                // If there are tasks that the user can view then show the taskgroup
                return (count($tasks) > 0);
            }

        });
    }
    
    $w->ctx("taskgroups", $taskgroups);
    $w->ctx("redirect", $params['redirect']);
}
