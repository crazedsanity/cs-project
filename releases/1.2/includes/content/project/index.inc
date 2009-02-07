<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */

	
		//check to see if something's been selected to expand...
		$expandArr = $page->ui->get_cache("expandArr");
		if(!is_array($expandArr)) {
			$expandArr = array();
		}
		$expandId = $_GET['expandId'];
		$expandAction = $_GET['expand'];
		if($expandAction && $expandId) {
			//let's add it to the array.
			if($expandAction == "open" && !isset($expandArr[$expandId])) {
				//add it to the array!!!
				$expandArr[$expandId] = $_GET['level'];
			}
			elseif($expandAction == "close"  && isset($expandArr[$expandId])) {
				//remove it, so it's not expanded anymore.
				unset($expandArr[$expandId]);
			}
		}
		$page->ui->set_cache("expandArr", $expandArr);
		
		#$page->change_content(html_file_to_string("modules/project/project_main.tmpl"));
		$page->set_all_block_rows("content");
		
		//set some template vars for the filtering.
		$page->add_template_var("filter_keyword", $filterData['keyword']);
		$page->add_template_var("filter_field_". $filterData['field'] ."_selected", "selected");
		$page->add_template_var("filter_status_id_". $filterData['status_id'] ."_selected", "selected");
			
		//show the main (root) level projects.
		$sortArr = $page->ui->get_cache("$module/currentSort");
		$projectsArr = $proj->list_projects(0,$sortArr,$filter);
		$page->add_template_var("project_row", process_row($page, $projectsArr, $expandArr, $page->templateRows['project_row']));
		
		//loop through the sub-projects.
		process_subProjects($page, $proj, $filter);


?>
