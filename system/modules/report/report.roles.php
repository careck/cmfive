<?php

// given user interface employs tab, templates control display of tabs based on user role.

function role_report_admin_allowed(&$w,$path) {
    return preg_match("/report(-.*)?\//",$path);
}

function role_report_editor_allowed(&$w,$path) {
    return preg_match("/report\//",$path);
}

function role_report_user_allowed(&$w,$path) {
    return preg_match("/report\//",$path);
}
