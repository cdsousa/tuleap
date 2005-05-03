<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

require('./tracker_import_utils.php');
require_once('www/project/export/project_export_utils.php');

$Language->loadLanguageMsg('tracker/tracker');

if($group_id && $atid && $user_id) {

  //   parse the CSV file and show the parse report *****************************************************
  if ($mode == "parse") {
		
    //if (!$file_upload) {
      //if (!$data) {
      //	exit_missing_param();
      //} else {
    //$csv_filename = tempnam("","imp");
    //$csv_file = fopen($csv_filename,'w');
    //fwrite($csv_file,stripslashes($data));
    //fclose($csv_file);
    //$is_tmp = true;
    //}
    //} else {
      if (!file_exists($csv_filename) || !is_readable($csv_filename)) {
	exit_missing_param();
      }
      $is_tmp = false;
      //}

    
    $ok = parse($csv_filename,$group_id,$is_tmp,
		$used_fields,$fields,$artifacts_data,
		$aid_column,$submitted_by_column,$submitted_on_column,
		$number_inserts,$number_updates,
		$errors);

    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));

    echo '<h2>'.$Language->getText('tracker_import','parse_report').'</h2>';
    if (!$ok) {
      show_errors($errors);
    } else {
      echo $Language->getText('tracker_import','ready',array(($number_inserts+$number_updates),$number_inserts, $number_updates))."<br><br>\n";
      show_parse_results($used_fields,$fields,$artifacts_data,$aid_column,$submitted_by_column,$submitted_on_column,$group_id);
    }

    $ath->footer(array());


    //   import the artifacts that the user has accepted from the parse report **********************************
  } else if ($mode == "import") {  
    
    for ($i=0; $i < $count_artifacts; $i++) {
      for ($c=0; $c < count($parsed_labels); $c++) {
	$label = $parsed_labels[$c];
	$var_name = "artifacts_data_".$i."_".$c;
	$data[$label] = $$var_name;
	//echo "insert $label,".$$var_name." into data<br>";
      }
      $artifacts_data[] = $data;
    }
    
    $ok = update_db($parsed_labels,$artifacts_data,$aid_column,$errors);
    
    if ($ok) $feedback = $Language->getText('tracker_import','success_import',$count_artifacts)." ";
    else $feedback = $errors;

    //update group history
    group_add_history('import',$ath->getName(),$group_id);

    require('./browse.php');
    

    //   screen showing the allowed input format of the CSV files *************************************************
  } else if ($mode == "showformat") {

    // project_export_utils is using $at instead of $ath
    $at = $ath;
    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));
    $sql = $ath->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
    
    //we need only one single record
    $sql .= " LIMIT 1";

    //get all mandatory fields
    $mand_list = mandatory_fields($ath);
    
    // Add the 2 fields that we build ourselves for user convenience
    // - All follow-up comments
    // - Dependencies
    
    $col_list[] = 'follow_ups';
    $col_list[] = 'is_dependent_on';
    $col_list[] = 'add_cc';
    $col_list[] = 'cc_comment';

    // TODO: Localize this properly by adding those 4 fields to the artifact table
    // (standard fields) and the artifact field table with a special flag and make sure
    // all tracker scripts handle them properly
    // For now make a big hack!!
    $field = $art_field_fact->getFieldFromName('submitted_by');
    if (strstr($field->getLabel(),"ubmit")) {
	// Assume English
	$lbl_list['follow_ups'] = 'Follow-up Comments';
        $lbl_list['is_dependent_on'] = 'Depend on';
        $lbl_list['add_cc'] = 'CC List';
        $lbl_list['cc_comment'] = 'CC Comment';
    
	$dsc_list['follow_ups'] = 'All follow-up comments in one chunck of text';
	$dsc_list['is_dependent_on'] = 'List of artifacts this artifact depends on';
	$dsc_list['add_cc'] = 'List of persons to receive a carbon-copy (CC) of the email notifications (in addition to submitter, assignees, and commenters)';
	$dsc_list['cc_comment'] = 'Explain why these CC names were added and/or who they are';
    } else {
        // Assume French
	$lbl_list['follow_ups'] = 'Commentaires';
        $lbl_list['is_dependent_on'] = 'D�pend de';
        $lbl_list['add_cc'] = 'Liste CC';
        $lbl_list['cc_comment'] = 'Commentaire CC';
    
	$dsc_list['follow_ups'] = 'Tout le fil de commentaires en un seul bloc de texte';
	$dsc_list['is_dependent_on'] = 'Liste des artefacts dont celui-ci d�pend';
	$dsc_list['add_cc'] = 'Liste des pesonnes recevant une copie carbone of persons to receive a carbon-copy (CC) des notifications e-mail (en plus de la personne qui l\'a soumis, � qui on l\'a confi� ou qui a post� un commentaire)';
	$dsc_list['cc_comment'] = 'Explique pourquoi ces personnes sont en CC ou qui elles sont';
    }        
    
    $eol = "\n";
    
    $result=db_query($sql);
    $rows = db_numrows($result); 

    echo '<h3>'.$Language->getText('tracker_import','format_hdr'),'</h3>';
    echo '<p>'.$Language->getText('tracker_import','format_msg'),'<p>';

    if ($rows > 0) { 
      $record = pick_a_record_at_random($result, $rows, $col_list);
      } else {
      $record = $ath->buildDefaultRecord();
      }
    prepare_artifact_record($at,$fields,$atid,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record,$mand_list);
    
    echo '<br><br><h4>'.$Language->getText('tracker_import','sample_cvs_file').'</h4>';
    echo build_csv_header($col_list,$lbl_list);
    echo '<br>';
    echo build_csv_record($col_list,$record);
    
    $ath->footer(array());


    //   screen accepting the CSV file to be parsed **************************************************************
  } else {
    
    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));

    echo '<h3>'.$Language->getText('tracker_import','import_new_hdr', array(help_button('ArtifactImport.html'))).'</h3>';
    echo '<p>'.$Language->getText('tracker_import','import_new_msg',array('/tracker/index.php?group_id='.$group_id.'&atid='.$atid.'&user_id='.$user_id.'&mode=showformat&func=import')).'</p>';

    echo '
	    <FORM NAME="importdata" action="'.$PHP_SELF.'" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="group_id" value="'.$group_id.'">            
            <INPUT TYPE="hidden" name="atid" value="'.$atid.'">            
            <INPUT TYPE="hidden" name="func" value="import">
            <INPUT TYPE="hidden" name="mode" value="parse">

			<table border="0" width="75%">
			<tr>
			<th> ';//<input type="checkbox" name="file_upload" value="1"> 
    echo '<B>'.$Language->getText('tracker_import','upload_file').'</B></th>
			<td> <input type="file" name="csv_filename" size="50">
                 <br><span class="smaller"><i>'.$Language->getText('tracker_import','max_upload_size',formatByteToMb($sys_max_size_upload)).'</i></span>
			</td>
			</tr>';

    //<tr>
    //<th>OR Paste Artifact Data (in CSV format):</th>
    //<td><textarea cols="60" rows="10" name="data"></textarea></td>
    //</tr>
    echo '
                        </table>

			<input type="submit" value="'.$Language->getText('tracker_import','submit_info').'">

	    </FORM> '; 
    $ath->footer(array());
    
  } // end else.
  
} else {
  exit_no_group();
}

?>
