<?php

//==============================
// Message box: Error
//==============================
function WPDebuggerError($msg, $showButton, $url, $arg) {
	$rand = rand(1,9999);
        echo '<div id="WPDebugBox_'.$rand.'" class="errorBox">'.$msg;
        if($showButton) {
                addFixButton($rand, $url, $arg);
        }
        echo '</div>';
}

//==============================
// Message box: Notice
//==============================
function WPDebuggerNotice($msg, $showButton, $url, $arg) {
	$rand = rand(1,9999);
	echo '<div id="WPDebugBox_'.$rand.'" class="noticeBox">'.$msg;
	if($showButton) {
		addFixButton($rand, $url, $arg);
	}
	echo '</div>';
}

//==============================
// Message box: Success!
//==============================
function WPDebuggerSuccess($msg) {
        echo '<div id="message" class="successBox">'.$msg.'</div>';

}


//==============================
// Constructs the plugin menu
//==============================
function wp_debugger_construct_menu() {
	add_management_page( 'WP Debugger', 'WP Debugger', 5, 'wp_debugger_index', 'wp_debugger_construct_index_page' );
}

//==============================
// Display fix button
//==============================
function addFixButton($id, $url, $arg) {
                echo '<input type="button" value="Fix now" id="button_'.$id.'" />';
                echo "<script type='text/javascript'>
                        jQuery('input#button_{$id}').click(function() {
				jQuery('div#WPDebugBox_{$id}').html(WPDshowLoader());
                                htmlobj=jQuery.post(ajaxurl, {action:'wp_debugger_check', what:'{$url}', arg:'{$arg}'},function(resp){
                                         jQuery('div#WPDebugBox_{$id}').html(resp);
                                });
                        });
                     </script>";
}

//==============================
// Constructs the plugins frontpage
//==============================
function wp_debugger_construct_index_page() {

	//Construct HTML for index page
	echo '<h2>WP Debugger</h2>
	<link rel="stylesheet" href="../wp-content/plugins/wp-debugger/style.css" type="text/css" media="all" />
	<table border="0" cellpadding="5" cellspacing="10" width="100%">
		<tr>
			<td valign="top" width="50%">

                                <!-- Introduction -->
                                <div id="poststuff">
                                        <div class="postbox">
                                                <h3 class="hndle">Introduction</h3>
                                                <div class="inside">
                                                        <b>Introduction</b>
							<p>This page will let you check the health of different parts of your Wordpress installation. Simply click
							start on any of the categories you want to check and the test results will appear in the corresponding 
							box. If any issuies is detected, you will either get the ability to fix it with one click or more information
							on how to fix the issue.</p>

							<b>Feedback/Support</b>
							<p>If you have noticed any problems with this plugin or have suggestions to any new checks that should be performed,
							feel free to contact me via e-mail: tor.henning@gmail.com.</p>
                                                </div>
                                        </div>
                                </div>

				<!-- Database checking -->
				<div id="poststuff">
					<div class="postbox">
						<h3 class="hndle">Database consistency check</h3>
						<div class="inside" id="WPDRunDBResult">
							<div style="text-align:center">
        							<input id="WPDRunDB" type="submit" name="options_save" class="button-primary" value="Run database consistency check" />
							</div>
						</div>
					</div>
				</div>
			</td>

			<td valign="top" width="50%">

                                <!-- File checking -->
                                <div id="poststuff">
                                        <div class="postbox">
                                                <h3 class="hndle">File consistency check</h3>
                                                <div class="inside" id="WPDRunFileResult">
                                                        <div style="text-align:center">
                                                                <input id="WPDRunFile" type="submit" name="options_save" class="button-primary" value="Run file consistency check" />
                                                        </div>
                                                </div>
                                        </div>
                                </div>

                                <!-- Wordpress internals checking -->
                                <div id="poststuff">
                                        <div class="postbox">
                                                <h3 class="hndle">Wordpress internals consistency check</h3>
                                                <div class="inside" id="WPDRunInternalResult">
                                                        <div style="text-align:center">
                                                                <input id="WPDRunInternal" type="submit" name="options_save" class="button-primary" value="Run Wordpress internals consistency check" />
                                                        </div>
                                                </div>
                                        </div>
                                </div>
			</td>
		</tr>
	</table>
	';


	//Construct JS for buttons
	echo "<script type='text/javascript'>

			function WPDshowLoader() {
				return '<div align=\"center\"><img src=\"../wp-content/plugins/wp-debugger/loading.gif\" /></div>';
			}

			jQuery('input#WPDRunDB').click(function() {
				jQuery('div#WPDRunDBResult').html(WPDshowLoader());
                        	htmlobj=jQuery.post(ajaxurl, {action:'wp_debugger_check', what:'db'},function(resp){
					 jQuery('div#WPDRunDBResult').html(resp);
				});
                        });

                        jQuery('input#WPDRunFile').click(function() {
				jQuery('div#WPDRunFileResult').html(WPDshowLoader());
                                htmlobj=jQuery.post(ajaxurl, {action:'wp_debugger_check', what:'file'},function(resp){
                                         jQuery('div#WPDRunFileResult').html(resp);
                                });
                        });

                        jQuery('input#WPDRunInternal').click(function() {
				jQuery('div#WPDRunInternalResult').html(WPDshowLoader());
                                htmlobj=jQuery.post(ajaxurl, {action:'wp_debugger_check', what:'internal'},function(resp){
                                         jQuery('div#WPDRunInternalResult').html(resp);
                                });
                        });

	     </script>";

}

//==============================
// Runs a specified test/check
//==============================
function wp_debugger_run_check() {
	$what = $_POST['what'];
	switch($what) {

		//Run DB checks
		case 'db':
			require("ajax/runDBCheck.php");
		break;

		//Run file checks
		case 'file':
			require("ajax/runFileCheck.php");
		break;

		//Run internal checks
		case 'internal':
			require("ajax/runInternalCheck.php");
		break;

		//Fix DB issues
		case 'optimizeDB':
			require("ajax/runDBoptimize.php");
		break;
		
		case 'repairDB':
			require("ajax/runDBrepair.php");	
		break;

		//Fix internal issues
		case 'fixInternal':
			require("ajax/runInternalFix.php");
		break;
	}
	die();
}
