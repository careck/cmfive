<?php

function comment_GET(Web $w){
    $p = $w->pathMatch("comment_id", "tablename", "object_id");

    $comment_id = intval($p["comment_id"]);
    $comment = $comment_id > 0 ? $w->Comment->getComment($comment_id) : new Comment($w);
    if ($comment === null){
        $comment = new Comment($w);
    }
    
    $help =<<<EOF
//italics//
**bold**
    		
* bullet list
* second item
** subitem
    
# numbered list
# second item
## sub item
    
[[URL|linkname]]
    
== Large Heading
=== Medium Heading
==== Small Heading
    
Horizontal Line:
---
EOF;
    
    $form = array(
        array(__("Comment"),"section"),
        array("", "textarea", "comment", $comment->comment, 100, 15, false),
    	array(__("Help"),"section"),
    	array("", "textarea", "-help",$help, 100, 5, false),
        array("", "hidden", "redirect_url", $w->request("redirect_url"))
    );

    // return the comment for display and edit
    $w->setLayout(null);
    $w->out(Html::form($form, $w->localUrl("/admin/comment/{$comment_id}/{$p["tablename"]}/{$p["object_id"]}"), "POST", __("Save")));
}

function comment_POST(Web $w){
    $p = $w->pathMatch("comment_id", "tablename","object_id");
    $comment_id = intval($p["comment_id"]);
    
    $comment = ($comment_id > 0 ? $w->Comment->getComment($comment_id) : new Comment($w));
    if ($comment === null){
        $comment = new Comment($w);
    }
    
    $comment->obj_table = $p["tablename"];
    $comment->obj_id = $p["object_id"];
    $comment->comment = strip_tags($w->request("comment"));
    $comment->insertOrUpdate();
    
    $redirectUrl = $w->request("redirect_url");

    if (!empty($redirectUrl)){
        $w->msg(__("Comment saved"), urldecode($redirectUrl));
    } else {
        $w->msg(__("Comment saved"), $w->localUrl($_SERVER["REQUEST_URI"]));
    }
}
