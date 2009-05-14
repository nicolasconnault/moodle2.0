<?php

$string['activeportfolios'] = 'Active portfolios';
$string['alreadyalt'] = 'Already exporting - please click here to resolve this transfer';
$string['addnewportfolio'] = 'Add a new portfolio';
$string['addtoportfolio'] = 'Save...';
$string['addalltoportfolio'] = 'Save all...';
$string['activeexport'] = 'Resolve active export';
$string['alreadyexporting'] = 'You already have an active portfolio export in this session. Before continuing, you must either complete this export, or cancel it.  Would you like to continue it? (No will cancel it)';
$string['availableformats'] = 'Available export formats';
$string['callercouldnotpackage'] = 'Failed to package up your data for export: original error was $a';
$string['cannotsetvisible'] = 'Cannot set this to visible - the plugin has been completely disabled because of a misconfiguration';
$string['configexport'] = 'Configure exported data';
$string['configplugin'] = 'Configure portfolio plugin';
$string['confirmexport'] = 'Please confirm this export';
$string['confirmsummary'] = 'Summary of your export';
$string['configure'] = 'Configure';
$string['continuetoportfolio'] = 'Continue to your portfolio';
$string['commonsettingsdesc'] = '<p>Whether a transfer is considered to take a \'Moderate\' or \'High\' amount of time changes whether the user is able to wait for the transfer to complete or not.</p><p>Sizes up to the \'Moderate\' threshold just happen immediately without the user being asked, and \'Moderate\' and \'High\' transfers mean they are offered the option but warned it might take some time.</p><p>Additionally, some portfolio plugins might ignore this option completely and force all transfers to be queued.</p>';
$string['deleteportfolio'] = 'Delete portfolio instance';
$string['destination'] = 'Destination';
$string['disabled'] = 'Sorry, but portfolio exports are not enabled in this site';
$string['displayarea'] = 'Export area';
$string['displayinfo'] = 'Export info';
$string['displayexpiry'] = 'Transfer expiry time';
$string['dontwait'] = 'Don\'t wait';
$string['err_uniquename'] = 'Portfolio name must be unique (per plugin)';
$string['enabled'] = 'Enable portfolios';
$string['enableddesc'] = 'This will allow administrators to configure remote systems for users to export content to';
$string['exporting'] = 'Exporting to portfolio';
$string['exportingcontentfrom'] = 'Exporting content from $a';
$string['exportcomplete'] = 'Portfolio export complete!';
$string['exportqueued'] = 'Portfolio export has been successfully queued for transfer';
$string['exportqueuedforced'] = 'Portfolio export has been successfully queued for transfer (the remote system has enforced queued transfers)';
$string['exportedpreviously'] = 'Previous exports';
$string['exportexceptionnoexporter'] = 'A portfolio_export_exception was thrown with an active session but no exporter object';
$string['exportexpired'] = 'Portfolio export expired';
$string['exportexpireddesc'] = 'You tried to repeat the export of some information, or start an empty export.  To do that properly you should go back to the original location and start again.  This sometimes happens if you use the back button after an export has completed, or by bookmarking an invalid url.';
$string['failedtosendpackage'] = 'Failed to send your data to the selected portfolio system: original error was $a';
$string['failedtopackage'] = 'Could not find files to package';
$string['filedenied'] = 'Access denied to this file';
$string['filenotfound'] = 'File not found';
$string['format_file'] = 'File';
$string['format_richhtml'] = 'HTML with attachments';
$string['format_plainhtml'] = 'HTML';
$string['format_image'] = 'Image';
$string['format_mbkp'] = 'Moodle Backup Format';
$string['format_video'] = 'Video';
$string['format_text'] = 'Plain Text';
$string['hidden'] = 'Hidden';
$string['highfilesizethreshold'] = 'High transfer filesize';
$string['highfilesizethresholddesc'] = 'Filesizes over this threshold will be considered to take a high amount of time to transfer';
$string['highdbsizethreshold'] = 'High transfer dbsize';
$string['highdbsizethresholddesc'] = 'Number of db records over which will be considered to take a high amount of time to transfer';
$string['insanesubject'] = 'Some portfolio instances automatically disabled';
$string['insanebody'] = 'Hi!  You are receiving this message as an administrator of $a->sitename.

Some portfolio plugin instances have been automatically disabled due to misconfigurations.  This means that users can not currently export content to these portfolios.

The list of portfolio plugin instances that have been disabled is:

$a->textlist

This should be corrected as soon as possible, by visiting $a->fixurl.
';
$string['insanebodyhtml'] = '<p>Hi! You are receiving this message as an administrator of $a->sitename.</p>
$a->htmllist
<p>This should be corrected as soon as possible, by visiting <a href=\"$a->fixurl\">the portfolio configuration pages</a></p>';
$string['insanebodysmall'] = 'Hi!  You are receiving this message as an administrator of $a->sitename.  Some portfolio plugin instances have been automatically disabled due to misconfigurations.  This means that users can not currently export content to these portfolios.  This should be corrected as soon as possible, by visiting $a->fixurl.';
$string['instancedeleted'] = 'Portfolio deleted successfully';
$string['instanceismisconfigured'] = 'Portfolio instance is misconfigured, skipping.  Error was: $a';
$string['instancenotsaved'] = 'Failed to save portfolio';
$string['instancenotdelete'] = 'Failed to delete portfolio';
$string['instancesaved'] = 'Portfolio saved successfully';
$string['invalidaddformat'] = 'Invalid add format passed to portfolio_add_button. ($a) Must be one of PORTFOLIO_ADD_XXX';
$string['invalidtempid'] = 'Invalid export id. maybe it has expired';
$string['invalidfileargument'] = 'Invalid file argument passed to portfolio_format_from_file - must be stored_file object';
$string['invalidfileareaargs'] = 'Invalid file area arguments passed to set_file_and_format_data - must contain contextid, filearea and itemid';
$string['invalidsha1file'] = 'Invalid call to get_sha1_file - either single or multifiles must be set';
$string['invalidpreparepackagefile'] = 'Invalid call to prepare_package_file - either single or multifiles must be set';
$string['invalidformat'] = 'Something is exporting an invalid format, $a';
$string['invalidinstance'] = 'Could not find that portfolio instance';
$string['invalidproperty'] = 'Could not find that property ($a->property of $a->class)';
$string['invalidexportproperty'] = 'Could not find that export config property ($a->property of $a->class)';
$string['invaliduserproperty'] = 'Could not find that user config property ($a->property of $a->class)';
$string['invalidconfigproperty'] = 'Could not find that config property ($a->property of $a->class)';
$string['invalidbuttonproperty'] = 'Could not find that property ($a) of portfolio_button';
$string['logs'] = 'Transfer logs';
$string['logsummary'] = 'Previous successful transfers';
$string['manageportfolios'] = 'Manage portfolios';
$string['manageyourportfolios'] = 'Manage your portfolios';
$string['missingcallbackarg'] = 'Missing callback argument $a->arg for class $a->class';
$string['moderatefilesizethreshold'] = 'Moderate transfer filesize';
$string['moderatefilesizethresholddesc'] = 'Filesizes over this threshold will be considered to take a moderate amount of time to transfer';
$string['moderatedbsizethreshold'] = 'Moderate transfer dbsize';
$string['moderatedbsizethresholddesc'] = 'Number of db records over which will be considered to take a moderate amount of time to transfer';
$string['multipledisallowed'] = 'Trying to create another instance of a plugin that has disallowed multiple instances ($a)';
$string['mustsetcallbackoptions'] = 'You must set the callback options either in the portfolio_add_button constructor or using the set_callback_options method';
$string['noavailableplugins'] = 'Sorry, but there are no available portfolios for you to export to';
$string['nocallbackfile'] = 'Something in the module you\'re trying to export from is broken - couldn\'t find a required file ($a)';
$string['nocallbackclass'] = 'Could not find the callback class to use ($a)';
$string['nocommonformats'] = 'No common formats between any available portfolio plugin and the calling location $a';
$string['noclassbeforeformats'] = 'You must set the callback class before calling set_formats in portfolio_button';
$string['nopermissions'] = 'Sorry but you do not have the required permissions to export files from this area';
$string['nonprimative'] = 'A non primative value was passed as a callback argument to portfolio_add_button.  Refusing to continue.  The key was $a->key and the value was $a->value';
$string['notexportable'] = 'Sorry, but the type of content you are trying to export is not exportable';
$string['notimplemented'] = 'Sorry, but you are trying to export content in some format that is not yet implemented ($a)';
$string['notyetselected'] = 'Not yet selected';
$string['notyours'] = 'You are trying to resume a portfolio export that doesn\'t belong to you!';
$string['nouploaddirectory'] = 'Could not create a temporary directory to package your data into';
$string['portfolio'] = 'Portfolio';
$string['portfolios'] = 'Portfolios';
$string['plugin'] = 'Portfolio plugin';
$string['plugincouldnotpackage'] = 'Failed to package up your data for export: original error was $a';
$string['pluginismisconfigured'] = 'Portfolio plugin is misconfigured, skipping.  Error was: $a';
$string['queuesummary'] = 'Currently queued transfers';
$string['returntowhereyouwere'] = 'Return to where you were';
$string['save'] = 'Save';
$string['selectedformat'] = 'Selected export format';
$string['selectedwait'] = 'Selected to wait?';
$string['selectplugin'] = 'Select destination';
$string['someinstancesdisabled'] = 'Some configured portfolio plugin instances have been disabled either because they are misconfigured or rely on something else that is';
$string['somepluginsdisabled']  = 'Some entire portfolio plugins have been disabled because they are either misconfigured or rely on something else that is:';
$string['sure'] = 'Are you sure you want to delete \'$a\'?  This cannot be undone.';
$string['thirdpartyexception'] = 'A third party exception was thrown during portfolio export ($a). Caught and rethrown but this should really be fixed';
$string['transfertime'] = 'Transfer time';
$string['unknownplugin'] = 'Unknown (may have since been removed by an administrator)';
$string['wanttowait_moderate'] = 'Do you want to wait for this transfer? It might take a few minutes';
$string['wanttowait_high'] = 'It is not recommended that you wait for this transfer to complete, but you can if you\'re sure and know what you\'re doing';
$string['wait'] = 'Wait';
?>
