<?php

function printqueue_GET(Web $w) {
    $path = realpath(FILE_ROOT . "print");
    $exclude = array("THUMBS.db");
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    
    $table_data = array();
    $table_header = array("Name", "Size", "Date Created", "Actions");
    
    foreach($objects as $name => $object){
        $filename = $object->getFilename();
        // Ignore files starting with '.' and in exclude array
        if ($filename[0] === '.' || in_array($filename, $exclude)) {
            continue;
        }
        
        $table_data[] = array(
            Html::a("/uploads/print/" . $filename, $filename),
            // Function below in functions.php
            humanReadableBytes($object->getSize()),
            date("H:i d/m/Y", filectime($name)),
            Html::box("/admin/printfile?filename=" . urlencode($name), "Print", true) . " " .
            Html::b("/admin/deleteprintfile?filename=" . urlencode($name), "Delete", "Are you sure you want to remove this file? (This is irreversible)")
        );
    }

    $w->out(Html::table($table_data, null, "tablesorter", $table_header));
}