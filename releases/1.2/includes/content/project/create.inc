<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */


		//set the "parent" arbitrarily (give it a default), then check the URL to see if it's specified.
		$parent = 0;
		if(is_numeric($_GET['parent'])) {
			$parent = $_GET['parent'];
		}
		//make sure all the projects aren't displayed...
		$showAllProjects = FALSE;
		
		//now set the proper content.
		create_page_title($page, array('title' => "Create New Project"));
		$page->add_template_var("parent", $parent);
		$page->add_template_var("submit_name", "Create Project");
	
		//create the "chef" and "participants" list.	
		//TODO: if creating a sub-project, select the list of users that had been selected for the parent...
		$page->add_template_var("select_linked_users", $proj->create_user_option_list($_SESSION['user_ID'],NULL,NULL,FALSE));
		$page->add_template_var("select_leader_uid", $proj->create_user_option_list($_SESSION['user_ID']));
		
		//add the priority thing.
		$priorityOptionLIst = create_priority_option_list(50);
		$page->add_template_var("select_priority", $priorityOptionLIst);
		
		//give a default for the begin & end dates.
		//TODO: if it's a sub-project (if $_GET['parent'] > 0), use the "end" value for the parent.
		$page->add_template_var("start_date", date("Y-m-d"));
		$page->add_template_var("deadline", strftime("%Y-%m-%d", strtotime("+1 month")));
		
		$page->add_template_var("parentProjectId", $_GET['parent']);
	

?>
