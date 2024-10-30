<?php
/*
Plugin Name: MeetingList
Plugin URI: http://www.sunflowerintergroupoa.org/meetinglist/
Description: Allows management of a list of meetings through WordPress. Originally developed to maintain meeting lists for organizations of 12-step groups (AA, OA, NA, CA, Al-Anon, etc.) Stores data in the database, features a variety of functions to allow display of publications in a list (eg for the sidebar) or on a page. Styled through CSS.
Version: 0.11
Author: RickB
Author URI: http://www.sunflowerintergroupoa.org
*/

/*
    MeetingList
    Copyright 2005 RickB
    http://www.sunflowerintergroupoa.org/wordpress-plugins/MeetingList/

    This wordpress plugin owes a debt to the example of Damselfly's Meetingfly plugin.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/*
 * $Id$
 */

// MeetingList Options
$MeetingList_version = "0.11";

// Install or upgrade the program
register_activation_hook(__FILE__, 'MeetingList_install');

function MeetingList_action_wp_head() {
    $mstyle =  "<style type='text/css' media='screen'>	@import url(";
    $mstyle .= $WP_SITEURL . "wp-content/plugins/meeting-list/meetinglist.css); </style>";
    echo $mstyle;
}

function MeetingList_install () {
    global $wpdb, $MeetingList_version;

    $table_name = $wpdb->prefix . "meeting_list";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name)
    {
        // Create a brand new install
        $sql = "CREATE TABLE " . $table_name . " (
                     meetingid INT(11) NOT NULL AUTO_INCREMENT,
                     mname VARCHAR(255) NOT NULL,
                     mday VARCHAR(255) NOT NULL,
                     mtime VARCHAR(255) NOT NULL,
                     mloc VARCHAR(255) NOT NULL,
                     maddr VARCHAR(255) NOT NULL,
                     mcity VARCHAR(255) NOT NULL,
                     mstate VARCHAR(255) NOT NULL,
                     mzip VARCHAR(255) NOT NULL,
                     mcontact VARCHAR(255) NOT NULL,
                     mcont_phone VARCHAR(255) NOT NULL,
                     mcont_email VARCHAR(255) NOT NULL,
                     mdescr TEXT NOT NULL,
                     mnotes TEXT NOT NULL,
                     mtype ENUM('open','closed') NOT NULL,
                     published ENUM('yes','no') NOT NULL,
                     visible ENUM('yes','no') NOT NULL,
                     UNIQUE KEY ID (meetingid)
                 );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option("MeetingList_version", $MeetingList_version, "Installed MeetingList version");

        echo "<div class=\"updated\"><p>MeetingList " . $MeetingList_version . " has been successfully installed. The plugin has created a database table named " . $table_name . ".<br /><br />If you deactivate the plugin later and want to remove the data, make sure to delete this table.</p></div>";
    }

    // Upgrade from a previous version
    $installed_version = get_option("MeetingList_version");

    if( $installed_version != $MeetingList_version || empty($installed_version) )
    {

        $sql = "CREATE TABLE ".$table_name." (
                     meetingid INT(11) NOT NULL AUTO_INCREMENT,
                     mname VARCHAR(255) NOT NULL,
                     mday VARCHAR(255) NOT NULL,
                     mtime VARCHAR(255) NOT NULL,
                     mloc VARCHAR(255) NOT NULL,
                     maddr VARCHAR(255) NOT NULL,
                     mcity VARCHAR(255) NOT NULL,
                     mstate VARCHAR(255) NOT NULL,
                     mzip VARCHAR(255) NOT NULL,
                     mcontact VARCHAR(255) NOT NULL,
                     mcont_phone VARCHAR(255) NOT NULL,
                     mcont_email VARCHAR(255) NOT NULL,
                     mdescr TEXT NOT NULL,
                     mnotes TEXT NOT NULL,
                     mtype ENUM('open','closed') NOT NULL,
                     published ENUM('yes','no') NOT NULL,
                     visible ENUM('yes','no') NOT NULL,
                     UNIQUE KEY ID (meetingid)
                 );";

        require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
        dbDelta($sql);

        update_option( "MeetingList_version", $MeetingList_version );

        echo "<div class=\"updated\"><p>Meeting List has been successfully updated to version " . $MeetingList_version . "</p></div>";

    }

}

// Add the management page to the administration panel; sink function for 'admin_menu' hook
function MeetingList_admin_menu()
{
    add_management_page('MeetingList', 'MeetingList', 8, 'meetinglist', 'MeetingList_manage');
}

// Handles the MeetingList management page
function MeetingList_manage()
{
    global $wpdb;

    // assuming this is a FORMS SUBMISSION then the parameters will be in _POSTS

    $updateaction = !empty($_POST['updateaction']) ? $_POST['updateaction'] : '';
    $meetingid = !empty($_POST['meetingid']) ? $_POST['meetingid'] : '';

    //BUT Delete and Edit links come from GETS not PUTS  so the parameters are in the URL
    // and so we have to get them from _GET ...   arrrghh -- so complex

    if (isset($_GET['action']) ):
    $action = $_GET['action'];
    $meetingid = intval($_GET['meetingid']);

    if ($action == 'delete_meeting')
    {
        $meetingid = intval($_GET['meetingid']);
        if (empty($meetingid))
        {
            ?><div class="error"><p><strong>Failure:</strong> No Meeting-ID given. I guess I deleted nothing successfully.</p></div><?php
        }
        else
        {
            $wpdb->query("DELETE FROM " . $wpdb->prefix . "meeting_list WHERE meetingid = '" . $meetingid . "'");
            $sql = "SELECT meetingid FROM " . $wpdb->prefix . "Meeting_List WHERE meetingid = '" . $meetingid . "'";
            $check = $wpdb->get_results($sql);
            if ( empty($check) || empty($check[0]->meetingid) )
            {
                ?><div class="updated"><p>Meeting Entry <?php echo $meetingid; ?> deleted successfully.</p></div><?php
            }
            else
            {
                ?><div class="error"><p><strong>Failure:</strong> Ninjas proved my kung-fu to be too weak to delete that entry.</p></div><?php
            }
        }
    } // end delete_meeting block
    endif;

    if ( $updateaction == 'update_meeting' )
    {
        $mname = !empty($_POST['mname']) ? $_POST['mname'] : '';
        $mloc = !empty($_POST['mloc']) ? $_POST['mloc'] : '';
        $mday = !empty($_POST['mday']) ? $_POST['mday'] : '';
        $mtime = !empty($_POST['mtime']) ? $_POST['mtime'] : '';
        $maddr = !empty($_POST['maddr']) ? $_POST['maddr'] : '';
        $mcity = !empty($_POST['mcity']) ? $_POST['mcity'] : '';
        $mstate = !empty($_POST['mstate']) ? $_POST['mstate'] : '';
        $mzip = !empty($_POST['mzip']) ? $_POST['mzip'] : '';
        $mcontact = !empty($_POST['mcontact']) ? $_POST['mcontact'] : '';
        $mcont_phone = !empty($_POST['mcont_phone']) ? $_POST['mcont_phone'] : '';
        $mcont_email = !empty($_POST['mcont_email']) ? $_POST['mcont_email'] : '';
        $mdescr = !empty($_POST['mdescr']) ? $_POST['mdescr'] : '';
        $meetingnotes = !empty($_POST['mnotes']) ? $_POST['mnotes'] : '';
        $meetingtype = !empty($_POST['mtype']) ? $_POST['mtype'] : '';
        $published =!empty($_POST['published']) ? $_POST['published'] : '';
        $visible = !empty($_POST['visible']) ? $_POST['visible'] : '';



        if ( empty($meetingid) )
        {
            ?><div class="error"><p><strong>Failure:</strong> No meeting-id given. Can't save nothing. Giving up...</p></div><?php
        }
        else
        {
            $sql = "UPDATE " . $wpdb->prefix . "meeting_list SET mname = '" . $mname
            . "', mloc = '" . $mloc
            . "', maddr = '" . $maddr
            . "', mcity = '" . $mcity
            . "', mday = '" . $mday . "', mtime = '" . $mtime
            . "', mstate = '" . $mstate . "', mzip = '" . $mzip
            . "', mcontact = '" . $mcontact . "', mcont_phone = '" . $mcont_phone
            . "', mcont_email = '" . $mcont_email . "', mdescr = '" . $mdescr
            . "', mnotes = '" . $meetingnotes . "', mtype = '" . $meetingtype
            . "', published = '" . $published . "', visible = '" . $visible
            . "' WHERE meetingid = '" . $meetingid . "'";

            echo("sql1 " .$sql);

            $wpdb->get_results($sql);
            $sql = "SELECT meetingid FROM " . $wpdb->prefix . "meeting_list WHERE mname = '" . $mname . "' and mloc= '" . $mloc. "' and maddr = '" . $maddr . "' and mcity = '" . $mcity . "' and mtype = '" . $meetingtype . "' and published = '" . $published . "' and visible = '" . $visible . "' LIMIT 1";
            echo("testsql " . $sql);
            $check = $wpdb->get_results($sql);
            if ( empty($check) || empty($check[0]->meetingid) )
            {
                ?><div class="error"><p><strong>Failure:</strong> The Evil Monkey Overlord wouldn't let me update your entry. Try again?</p></div><?php
            }
            else
            {
                ?><div class="updated"><p>Meeting <?php echo $meetingid; ?> updated successfully.</p></div><?php
            }
        }
    } // end update_meeting block
    elseif ( $updateaction == 'add_meeting' )
    {
        $mname = !empty($_POST['mname']) ? $_POST['mname'] : '';
        $mloc= !empty($_POST['mloc']) ? $_POST['mloc'] : '';
        $maddr = !empty($_POST['maddr']) ? $_POST['maddr'] : '';
        $mcity = !empty($_POST['mcity']) ? $_POST['mcity'] : '';
        $mstate = !empty($_POST['mstate']) ? $_POST['mstate'] : '';
        $mzip = !empty($_POST['mzip']) ? $_POST['mzip'] : '';
        $mcontact = !empty($_POST['mcontact']) ? $_POST['mcontact'] : '';
        $mcont_phone = !empty($_POST['mcont_phone']) ? $_POST['mcont_phone'] : '';
        $mcont_email = !empty($_POST['mcont_email']) ? $_POST['mcont_email'] : '';
        $mdescr = !empty($_POST['mdescr']) ? $_POST['mdescr'] : '';
        $meetingnotes = !empty($_POST['mnotes']) ? $_POST['mnotes'] : '';
        $meetingtype = !empty($_POST['mtype']) ? $_POST['mtype'] : '';
        $published =!empty($_POST['published']) ? $_POST['published'] : '';
        $visible = !empty($_POST['visible']) ? $_POST['visible'] : '';

        echo( "mname " . $mname . "==");

        //		$sql = "INSERT INTO " . $wpdb->prefix . "Meeting_List SET mname = '" . $mname . "', mloc= '" . $mloc. "', maddr = '" . $maddr . "', mcity = '" . $mcity . "', imgsrc = '" . $imgsrc . "', excerpt = '" . $excerpt . "', meetingnotes = '" . $meetingnotes . "', meetingtype = '" . $meetingtype . "', published = '" . $published . "', visible = '" . $visible . "'";

        $sql = "INSERT INTO " . $wpdb->prefix . "meeting_list SET mname = '" . $mname
        . "', mloc= '" . $mloc . "', maddr = '" . $maddr . "', mcity = '" . $mcity
        . "', mday = '" . $mloc . "', mtime = '" . $mtime
        . "', mstate = '" . $mstate . "', mzip = '" . $mzip
        . "', mcontact = '" . $mcontact . "', mcont_phone = '" . $mcont_phone
        . "', mcont_email = '" . $mcont_email . "', mdescr = '" . $mdescr
        . "', mnotes = '" . $meetingnotes . "', mtype = '" . $meetingtype
        . "', published = '" . $published . "', visible = '" . $visible . "'";
        $sql="INSERT INTO ". $wpdb->prefix . "meeting_list ";
        $sql.="(mname,mloc,maddr,mcity,mday,mtime,mstate,mzip,mcontact,mcont_phone,mcont_email,mdescr,mnotes,mtype,published,visible) VALUES ";
        $sql.="('$mname','$mloc','$maddr','$mcity','$mday','$mtime','$mstate','$mzip','$mcontact','$mcont_phone','$mcont_email','$mdescr','$mnotes','$mtype','$published','$visible')";
        echo($sql);
        $wpdb->get_results($sql);

        $sql2 = "SELECT meetingid FROM " . $wpdb->prefix . "meeting_list WHERE mname = '" . $mname . "' and mloc= '" . $mloc. "' and maddr = '" . $maddr . "' and mcity = '" . $mcity . "' and mtype = '" . $meetingtype . "' and published = '" . $published . "' and visible = '" . $visible . "'";
        $check = $wpdb->get_results($sql2);
        if ( empty($check) || empty($check[0]->meetingid) )
        {
            ?><div class="error"><p><strong>Failure:</strong> Holy crap you destroyed the internet! That, or something else went wrong when I tried to insert the entry. Try again? </p>
<p>sql 1<?php echo $sql . " "  . $mname ?></p><p>sql2<?php echo $sql2 ?></p><p><?php echo mysql_error();?></p></div><?php
}
else
{
    ?><div class="updated"><p>Meeting ID <?php echo $check[0]->meetingid;?> added successfully.</p></div><?php
}
} // end add_meeting block
?>

<div class=wrap>
    <?php
    if ( $action == 'edit_meeting' )
    {
        ?>
    <h2><?php _e('Edit Meeting'); ?></h2>
    <?php
    if ( empty($meetingid) )
    {
        echo "<div class=\"error\"><p>I didn't get an entry identifier from the query string. Giving up...</p></div>";
    }
    else
    {
        MeetingList_editform('update_meeting', $meetingid);
        echo('<h2>Manage Meeting List</h2><a href="tools.php?page=meetinglist">Add meeting</a>');
        MeetingList_displaylist();
    }
}
else
{
    ?>
    <h2><?php _e('Add Entry'); ?></h2>
    <?php MeetingList_editform(); ?>

    <h2><?php _e('Manage Meeting List'); ?></h2>
    <a href="tools.php?page=meetinglist">Add meeting</a>
    <?php
    MeetingList_displaylist();
}
?>
</div><?php

}

// Displays the list of MeetingList entries
function MeetingList_displaylist()
{
    global $wpdb;

    $meetings = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "Meeting_List ORDER BY meetingid DESC");

    if ( !empty($meetings) )
    {
        ?>
<table width="100%" cellpadding="3" cellspacing="3">
    <tr>
        <th scope="col"><?php _e('ID') ?></th>
        <th scope="col"><?php _e('MeetingName') ?></th>
        <th scope="col"><?php _e('Day') ?></th>
        <th scope="col"><?php _e('Time') ?></th>
        <th scope="col"><?php _e('Location') ?></th>
        <th scope="col"><?php _e('Address') ?></th>
        <th scope="col"><?php _e('City') ?></th>
        <th scope="col"><?php _e('Published') ?></th>
        <th scope="col"><?php _e('Visible') ?></th>
        <th scope="col"><?php _e('Edit') ?></th>
        <th scope="col"><?php _e('Delete') ?></th>
    </tr>
    <?php
    $class = '';
    foreach ( $meetings as $meeting )
    {
        $class = ($class == 'alternate') ? '' : 'alternate';
        ?>
    <tr class="<?php echo $class; ?>">
        <th scope="row"><?php echo $meeting->meetingid; ?></th>
        <td><?php echo $meeting->mname ?></td>
        <td><?php echo $meeting->mday ?></td>
        <td><?php echo $meeting->mtime ?></td>
        <td><?php echo $meeting->mloc ?></td>
        <td><?php echo $meeting->maddr ?></td>
        <td><?php echo $meeting->mcity; ?></td>
        <td><?php echo $meeting->published=='yes' ? 'Yes' : 'No'; ?></td>
        <td><?php echo $meeting->visible=='yes' ? 'Yes' : 'No'; ?></td>
        <td><a href="tools.php?page=meetinglist&amp;action=edit_meeting&amp;meetingid=<?php echo $meeting->meetingid;?>" class="edit"><?php echo __('Edit'); ?></a></td>
        <td><a href="tools.php?page=meetinglist&amp;action=delete_meeting&amp;meetingid=<?php echo $meeting->meetingid;?>" class="delete" onclick="return confirm('Are you sure you want to delete this entry?')"><?php echo __('Delete'); ?></a></td>
    </tr>
    <?php
}
?>
</table>
<?php
}
else
{
?>
<p><?php _e("You haven't entered any meeting entries yet.") ?></p>
<?php
}
}

// Displays the add/edit form
function MeetingList_editform($mode='add_meeting', $meetingid=false)
{
global $wpdb;
$data = false;

if ( $meetingid !== false )
{
// this next line makes me about 200 times cooler than you.
if ( intval($meetingid) != $meetingid )
{
    echo "<div class=\"error\"><p>Something -bad- happened.  I can not do that!</p></div>";
    return;
}
else
{
    $data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "Meeting_List WHERE meetingid = '" . $meetingid . " LIMIT 1'");
    if ( empty($data) )
    {
        echo "<div class=\"error\"><p>I couldn't find a meeting entry linked up with that identifier. Giving up...</p></div>";
        return;
    }
    $data = $data[0];
}
}

?>
<form name="meetingform" id="meetingform" class="wrap" method="post" action="">
    <input type="hidden" name="updateaction" value="<?php echo $mode?>">
    <input type="hidden" name="meetingid" value="<?php echo $meetingid?>">
    <input type="hidden" name="rickb" value="RickB">
    <div id="item_manager">
        <div style="float: left; width: 98%; clear: both;" class="top">

            <div style="float: right; width: 150px;" class="top">
                <fieldset class="small"><legend><?php _e('Meeting Type'); ?></legend>
                    <input type="radio" name="meeting_meetingtype" class="input" value="novel"
                           <?php if ( empty($data) || $data->mtype=='open' ) echo "checked" ?>/> Open Meeting
                    <br />
                    <input type="radio" name="meeting_meetingtype" class="input" value="short"
                           <?php if ( !empty($data) && $data->mtype=='closed' ) echo "checked" ?>/> Closed Meeting
                </fieldset>
                <br />

                <fieldset class="small"><legend><?php _e('Published'); ?></legend>
                    <input type="radio" name="meeting_published" class="input" value="yes"
                           <?php if ( empty($data) || $data->published=='yes' ) echo "checked" ?>/> Yes
                    <br />
                    <input type="radio" name="meeting_published" class="input" value="no"
                           <?php if ( !empty($data) && $data->published=='no' ) echo "checked" ?>/> No
                </fieldset>
                <br />

                <fieldset class="small"><legend><?php _e('Visible'); ?></legend>
                    <input type="radio" name="meeting_visible" class="input" value="yes"
                           <?php if ( empty($data) || $data->visible=='yes' ) echo "checked" ?>/> Yes
                    <br />
                    <input type="radio" name="meeting_visible" class="input" value="no"
                           <?php if ( !empty($data) && $data->visible=='no' ) echo "checked" ?>/> No
                </fieldset>
            </div>

            <!-- List URL -->
            <fieldset class="small"><legend><?php _e('Meeting Name'); ?></legend>
                <input type="text" name="mname" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mname); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Day of Week'); ?></legend>
                <input type="text" name="mday" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mday); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Meeting Time'); ?></legend>
                <input type="text" name="mtime" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mtime); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Location description'); ?></legend>
                <input type="text" name="mloc" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mloc); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Street Address'); ?></legend>
                <input type="text" name="maddr" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->maddr); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('City'); ?></legend>
                <input type="text" name="mcity" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mcity); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('State'); ?></legend>
                <input type="text" name="mstate" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mstate); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Zip/Postal code'); ?></legend>
                <input type="text" name="mzip" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mzip); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Contact Name'); ?></legend>
                <input type="text" name="mcontact" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mcontact); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Contact Phone'); ?></legend>
                <input type="text" name="mcont_phone" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mcont_phone); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Contact Email'); ?></legend>
                <input type="text" name="mcont_email" class="input" size=45 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->mcont_email); ?>" />
            </fieldset>

            <fieldset class="small"><legend><?php _e('Description'); ?></legend>
                <textarea name="mdescr" class="input" cols=45 rows=7><?php if ( !empty($data) ) echo htmlspecialchars($data->mdescr); ?></textarea>
            </fieldset>

            <fieldset class="small"><legend><?php _e('Notes'); ?></legend>
                <textarea name="mnotes" class="input" cols=45 rows=7><?php if ( !empty($data) ) echo htmlspecialchars($data->mnotes); ?></textarea>
            </fieldset>

            <br />
            <input type="submit" name="save" class="button bold" value="Save &raquo;" />

        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>
    </div>
</form>
<?php
}

// MeetingList Functions

//Stolen from Events-Calendar 6
function MeetingList_strstr($haystack, $needle, $before_needle=FALSE) {
if(($pos=strpos($haystack,$needle))===FALSE) return FALSE;
if($before_needle) return substr($haystack,0,$pos);
else return substr($haystack,$pos+strlen($needle));
}
//End Stolen Code
// Print all meeting list entries on to a page, sorted by type
// -- I can keep the old MeetingList_page function, and just convert it into an embedded version.)
function MeetingList_page(&$content)
{
global $wpdb, $post;
$table_name = $wpdb->prefix . "meeting_list";
$meetingpage = "";
$return_this = $content;
if(strpos($return_this,'[MeetingList]')!==FALSE)
{
    //step one: do a search for the tag:
    $meetingpage = "<h3 class=\"MeetingList\">Meetings</h3>";
    $published = $wpdb->get_results("SELECT * FROM " . $table_name . " ORDER BY 'mday', 'mtime' ");
    if ( !empty($published) )
    {
        $meetingpage .= '<div class="mllist"><table class="mltable"><tbody><tr>';
        $meetingpage .= '<th>Name</th>';
        $meetingpage .= '<th>Day</th>';
        $meetingpage .= '<th>Time</th>';
        $meetingpage .= '<th>Location</th>';
        $meetingpage .= '<th>Address</th>';
        $meetingpage .= '<th>Contact</th>';
        $meetingpage .= '</tr>';

        $alt_class = 1;	// This will alternate between 1 and 0.

        foreach ( $published as $row )
        {
            $alt_class = $alt_class ? 0 : 1;	// Alternate the class
            $meetingpage .= '<tr class=mlrow' . $alt_class . '>';
            $meetingpage .= '<td><div class="mname">' . $row->mname . '</div></td>';
            $meetingpage .= '<td><div class="mday">'  . $row->day   . '</div></td>';
            $meetingpage .= '<td><div class="mtime">' . $row->mtime . '</div></td>';
            $meetingpage .= '<td><div class="mloc">'  . $row->mloc  . '</div></td>';
            $meetingpage .= '<td><div class="maddr">' . $row->maddr . '</div><br />';
            $meetingpage .= '<td><div class="mcity">' . $row->mcity . '</div>, ';
            $meetingpage .= '<div class="mstate">'. $row->mstate . '</div> ';
            $meetingpage .= '<div class="mzip">'  . $row->mzip  . '</div></td>';
            $meetingpage .= '<td><div class="mcontact">' . $row->mcontact . '</div><br />';
            $meetingpage .= '<div class="cont_phone">' . $row->mcont_phone . '</div><br />';
            $meetingpage .= '<div class="mcont_email">' . $row->mcont_email . '</div></td></tr>';
        }
        $meetingpage .= '</tbody></table></div>';
    }
    $meetingpage .= "<div class=\"sep\">&nbsp;</div>";
    $before_mf = MeetingList_strstr($post_content, '[MeetingList]', TRUE);
    $after_mf  = MeetingList_strstr($post_content, '[MeetingList]', FALSE);
}
$return_this = $before_mf . $meetingpage . $after_mf;
return $return_this;
}

//add_filter('the_content','MeetingList_embedfunction');
add_filter('the_content','MeetingList_page');

// Insert the MeetingList_admin_menu() sink into the plugin hook list for 'admin_menu'

add_action('admin_menu', 'MeetingList_admin_menu');
add_action('wp_head',      'MeetingList_action_wp_head');
?>