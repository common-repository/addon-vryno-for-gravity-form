<?php

/*
 * This file is using to connect with vryno Using Connection page.
 * url : page=TivrynoAddOn
 */
if (!defined('ABSPATH'))
    exit;
// Localize nonce to be used in JavaScript
// Enqueue your script first

// Define the path to the script file
$script_path = $plugin_url . 'js/my-validation-script.js';

// Get the file modification time
$script_version = md5_file($script_path);

wp_enqueue_script('my-validation-script', $plugin_url . "/js/my-validation-script.js", array('jquery'), $script_version, false);

// Then localize it
wp_localize_script(
    'my-validation-script',
    'ajax_object',
    array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my-ajax-nonce')
    )
);
//connect vryno code
echo '<div class="gform_tab_content" id="haedingmain">
        <div class="loader" ></div>
        <h2>Vryno</h2>
      <h3 class="vrynoheading">Connect with your Vryno account</h3>
      <table class="form-table" name="vryno_connect" id="vryno_connect">
          <tbody>
            <tr valign="top">
                <th scope="row">
                    <label class="th-label"  for="vryno_server_url">Vryno Account Url:</label>
                </th>
                <td>';
if (empty($select)) {
    echo '<input type="url" name="vryno_server_url" value="https://accounts.vryno.dev/oidc/token" id="vryno_server_url" class="regular-text" placeholder="Type your vryno account url to generate token" pattern="https://.*" size="30" required/>';
} else {
    echo '<input type="url" name="vryno_server_url" value="' . esc_attr($vryno_server_url) . '" id="vryno_server_url" class="regular-text" placeholder="' . esc_attr($vryno_server_url) . '" disabled/>';
}
echo '</td>
                <td>
                    <h4>example -- https://taccounts.vryno.dev/oidc/token, .in etc</h4>
                </td>
            </tr>
    
            <tr valign="top">
                <th scope="row">
                    <label class="th-label"  for="refresh_token">refresh_token:</label>
                </th>
                <td>';
if (empty($select)) {
    echo '<input type="text" name="refresh_token" value="" id="refresh_token" class="regular-text" placeholder="Type your refresh_token"/ required>';
} else {
    echo '<input type="text" name="refresh_token" value="' . esc_attr($refresh_token) . '" id="refresh_token" class="regular-text" placeholder="' . esc_attr($refresh_token) . '" required disabled/>';
}
echo '</td>
            </tr>
    
            <tr valign="top">
                <th scope="row">
                    <label class="th-label"  for="client_id">client_id :</label>
                </th>
                <td>';
if (empty($select)) {
    echo '<input type="text" name="client_id" value="CX3uppxzkZafq5V2d5SwSw" id="client_id" class="regular-text" placeholder="Type your client_id" required/>';
} else {
    echo '<input type="text" name="client_id" value="' . esc_attr($client_id) . '" id="client_id" class="regular-text" placeholder="' . esc_attr($client_id) . '"  required disabled/>';
}
echo '</td>
            </tr>
    
            <tr valign="top">
                <th scope="row">
                    <label class="th-label"  for="client_secret">client_secret :</label>
                </th>
                <td>';
if (empty($select)) {
    echo '<input type="text" name="client_secret" value="Omr5bNozbSwy6vkbbX0DVxL6csig5eg7NNLq4Nqta8h" id="client_secret" class="regular-text" placeholder="Type your client_secret"required/>';
} else {
    echo '<input type="text" name="client_secret" value="' . esc_attr($client_secret) . '" id="client_secret" class="regular-text" placeholder="' . esc_attr($client_secret) . '" required disabled/>';
}
echo '</td>
            </tr>
    
            <tr valign="top">
               <th scope="row">
                    <label class="th-label"  for="grant_type">grant_type :</label>
                </th>
                <td>
                    <input type="text" name="grant_type" value="refresh_token" id="grant_type" class="regular-text" placeholder="refresh_token" disabled/>
                </td>
            </tr>';
if (!empty($select)) {
    echo "<tr valign='top'>
               <th scope='row'>
                    <label class='th-label'  for='instance'>Instance :</label>
                </th>
                <td>
                <select name='instance_id' id='instance_id' disabled>";
    foreach ($instancearr as $instance) {
        $selected = '';
        if ($instance_id == $instance) {
            $selected = 'selected';
        }
        echo '<option value="' . esc_attr($instance) . '" ' . esc_attr($selected) . '>
                ' . esc_html($instance) . '
                </option>';
    }


    echo "</select>
                </td>
            </tr>";
} else {
    echo '<input type="hidden" name="instance_id" value="" id="instance_id" class="regular-text" />';
}
echo '<tr id="tr-button" valign="top">
                <td id="td-button">';
if (empty($select)) {
    echo '<input type="submit" class="button-save" value="Save" id="submit_vryno_details" onclick="connectwithvryno();"/>
                    <input type="submit" class="button button-primary" value="Edit" id="submit_vryno_update_details" onclick="recordupdate();" style="display:none;"/>';
} else {
    echo '<input type="submit" class="button-save" value="Save" id="submit_vryno_details" onclick="connectwithvryno();" style="display:none;"/>
                    <input type="submit" class="button button-primary" value="Edit" id="submit_vryno_update_details" onclick="recordupdate();"/>';
}
echo '</td>
                <td id="td-button">';

if (!empty($select)) {
    echo '<input type="submit" class="button-update" name="refreshvrynofield" id="refreshvrynofield" value="Refresh vryno CRM fields" onclick="updatenewfields();"/>';
}
echo '</td>
            </tr>
        </tbody>
      </table>
    </div>
<div class="" id="vrynotokenpagelink">
    <h2 style="color:red;"><strong><u>Important Notice : </u></strong></h2>
    <h3>Information for generate token in vryno CRM </h3>
    <h4>Please follow the link for the steps to generate your token <a href="https://www.vryno.com/crm/developer/docs/api/access-refresh.html">https://www.vryno.com/crm/developer/docs/api/access-refresh.html</a></h4>
    <h4>Copy and paste the following value in the scopes section <p style="color:blue;">  vrynoCRM.modules.ALL,vrynoCRM.settings.all</p></h4>
</div>
';

?>



</script>