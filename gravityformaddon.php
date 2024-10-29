<?php
/**
 * Plugin Name: Addon Vryno for Gravity Form
 * Description: Integrates Gravity Forms with Vryno allowing form submissions to be automatically sent to your Vryno account 
 * Version: 1.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Requires Plugins: Gravity Forms
 * Tested up to: 6.6
 * Author URI: www.vryno.com
 * Author: Vryno
 * Domain Path: /languages/ 
 * License: GPL v2 or later
 * Text Domain: addon-vryno-for-gravity-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die();
}

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_plugin_active('gravityforms/gravityforms.php')) {
    deactivate_plugins(plugin_basename(__FILE__));
    // Redirect user to the plugins page with a status that the plugin was deactivated
    add_action('admin_init', 'custom_redirect_on_plugin_deactivation');

    function custom_redirect_on_plugin_deactivation()
    {
        // Redirect to the plugins page
        wp_redirect(admin_url('plugins.php'));
        exit;
    }
    //echo "<div class='error notice'><h3>You can't use <span style='color:blue;'>TI Gravity vryno-test Addon </span>plugin untill <span style='color:blue;'> Gravity Form </span> Plugin is activated.</h3></div>";
} else {
    require_once('class-gfvrynoaddon.php');
    global $avgf_db_version;
    $avgf_db_version = '1.0';

    add_action('gform_loaded', array('avgf_VrynoAddOn', 'avgf_vryno_load'), 5);
    function avgf_vryno_addon()
    {
        return avgf_VrynoAddOn::get_instance();
    }
    register_activation_hook(__FILE__, array('avgf_FormsAddon', 'avgf_add_table'));
    register_deactivation_hook(__FILE__, array('avgf_FormsAddon', 'avgf_uninstall_hook'));
    add_action('wp_ajax_refresh_fields', array('avgf_FormsAddon', 'avgf_refresh_fields'));
    add_action('wp_ajax_update_mapping', array('avgf_FormsAddon', 'avgf_update_mapping'));
    add_action('wp_ajax_delete_mapping', array('avgf_FormsAddon', 'avgf_delete_mapping'));
    add_action('wp_ajax_nopriv_vryno_connect', array('avgf_FormsAddon', 'avgf_vryno_connect'));
    add_action('wp_ajax_vryno_connect', array('avgf_FormsAddon', 'avgf_vryno_connect'));
    add_filter('gform_toolbar_menu', array('avgf_FormsAddon', 'avgf_toolbar'), 10, 2);
    add_filter('gform_addon_navigation', array('avgf_FormsAddon', 'avgf_create_mapping_menu'));
}

/**
 * Class avgf_FormsAddon
 *
 * Facilitates the creation of the Gravity Forms Addon 
 *
 */
class avgf_FormsAddon
{
    /**
     * Here Connecting Vryno 
     * @param $refresh_token     Argument provided in the post
     * @param $client_id         Argument provided in the post
     * @param $client_secret     Argument provided in the post
     * @param $vryno_server_url  Argument provided in the post
     * @param $instance_id       Argument provided in the post in updating instance
     * This function handles three apis of vryno. 
     * In First getting access token from vryno by sending above params.
     * In second getting instances from vryno so that can select in which instance want to add the leads.
     * In third getting fields from vryno. So that mapping of vryno fields can be done with gravity form fields.
     */
    public static function avgf_vryno_connect()
    {
        global $wpdb;
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'my-ajax-nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        // Check if $_POST['refresh_token'] is set and not empty
        if (isset($_POST['refresh_token']) && !empty($_POST['refresh_token'])) {
            // Sanitize and validate the refresh_token
            $sanitized_refresh_token = sanitize_text_field(wp_unslash($_POST['refresh_token']));

            // Escape the sanitized refresh_token for safe use in queries or output
            $refresh_token = esc_sql($sanitized_refresh_token);

            // Now you can use $refresh_token in your queries or output
        } else {
            wp_send_json_error('Refresh token is not valid');
        }
        // Check if $_POST['client_id'] is set and not empty
        if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
            // Sanitize and validate the client_id
            $sanitized_client_id = sanitize_text_field(wp_unslash($_POST['client_id']));

            // Escape the sanitized client_id for safe use in queries or output
            $client_id = esc_sql($sanitized_client_id);

            // Now you can use $client_id in your queries or output
        } else {
            wp_send_json_error('Client Id is not valid');
        }
        // Check if $_POST['client_secret'] is set and not empty
        if (isset($_POST['client_secret']) && !empty($_POST['client_secret'])) {
            // Sanitize and validate the client_secret
            $sanitized_client_secret = sanitize_text_field(wp_unslash($_POST['client_secret']));

            // Escape the sanitized client_secret for safe use in queries or output
            $client_secret = esc_sql($sanitized_client_secret);

            // Now you can use $client_secret in your queries or output
        } else {
            wp_send_json_error('Client Secret is not valid');
        }
        // Sanitize, validate, and escape the $_POST['vryno_server_url']
        $vryno_server_url = isset($_POST['vryno_server_url']) ? esc_url_raw(trim($_POST['vryno_server_url'])) : '';

        // Validate the URL (optional, depending on use case)
        if (empty($vryno_server_url) || !filter_var($vryno_server_url, FILTER_VALIDATE_URL)) {
            // Handle invalid URL
            $vryno_server_url = ''; // or set to a default value, or show an error
        }
        $email = "";
        $password = "";
        $lastChar = strlen($vryno_server_url) - 1;
        if ($vryno_server_url[$lastChar] != "/") {
            $url = $vryno_server_url;
        } else {
            $url = rtrim($vryno_server_url, '/');
        }
        $queryresult = "";
        // Prepare the SQL query
        // Prepare the SQL query
        global $wpdb;

        $result = $wpdb->get_results("SELECT `vryno_server_url`, `refresh_token`, `client_id`, `client_secret`, `email`, `password`, `grant_type`, `owner_id`, `instance_id` 
                              FROM {$wpdb->prefix}tigfaddon_vrynotoken");
        $instance_id = sanitize_text_field($_POST['instance_id']);
        if (empty($result)) {
            $queryresult = $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}tigfaddon_vrynotoken 
    (`vryno_server_url`, `refresh_token`, `client_id`, `client_secret`, `email`, `password`, `grant_type`, `owner_id`, `instance_id`) 
    VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    $url,
                    $refresh_token,
                    $client_id,
                    $client_secret,
                    $email,
                    $password,
                    'refresh_token',
                    '69da4575-6b9e-41aa-82ed-17088cb307cb',
                    'test-plu'
                )
            );
        } else {

            $queryresult = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}tigfaddon_vrynotoken 
    SET `vryno_server_url`=%s, `refresh_token`=%s, `client_id`=%s, `client_secret`=%s, `email`=%s, `password`=%s, `grant_type`=%s, `owner_id`=%s, `instance_id`=%s",
                    $url,
                    $refresh_token,
                    $client_id,
                    $client_secret,
                    $email,
                    $password,
                    'refresh_token',
                    '69da4575-6b9e-41aa-82ed-17088cb307cb',
                    'test-plu'
                )
            );
        }
        $delrsult = $wpdb->query("TRUNCATE " . $wpdb->prefix . 'tigfaddon_mapping');
        self::avgf_connect_api($refresh_token, $client_id, $client_secret, $url, $instance_id, $email, $password); //Passing Params in next function avgf_connect_api
    }

    /**
     * Here Connecting Vryno 
     * @param $refresh_token     Argument provided in the post
     * @param $client_id         Argument provided in the post
     * @param $client_secret     Argument provided in the post
     * @param $url               Argument provided in the post
     * @param $email             Argument provided in the post
     * @param $password          Argument provided in the post
     * @param $instance_id       Argument provided in the post in updating instance
     * This function handles three apis of vryno. 
     * In First getting access token from vryno by sending above params.
     * In second getting instances from vryno so that can select in which instance want to add the leads.
     * In third getting fields from vryno. So that mapping of vryno fields can be done with gravity form fields.
     */
    public static function avgf_connect_api($refresh_token, $client_id, $client_secret, $url, $instance_id, $email, $password)
    {
        global $wpdb;
        //Api getting access token from vryno by sending above params.
        $curlresponse = wp_remote_post(
            $url,
            array(
                'method' => 'POST',
                'timeout' => 45,
                'headers' => array(),
                'body' => array(
                    'refresh_token' => $refresh_token,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type' => 'refresh_token'
                ),
            )
        );
        $response = $curlresponse['body'];
        $token = json_decode($response)->access_token;
        $error = json_decode($response)->error;
        //getting access token from above api otherwise handling error
        if (!empty($token)) {
            echo "<div class='updated notice' id='adminotice'> your token has been successfully generated. </div>";
        } else if (!empty($error)) {
            echo "<div class='error notice' id='adminotice'> there is an error in generating token {error: " . esc_html($error) . "} </div>";

            $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "tigfaddon_vrynofields");
            $wpdb->query("CREATE TABLE " . $wpdb->prefix . "tigfaddon_vrynofields (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    vrynofieldname varchar(255) NOT NULL,
    vrynofieldlabel varchar(255) NOT NULL,
    vrynofieldtype varchar(255) NOT NULL,
    vrynofieldid varchar(255) NOT NULL,
    mandatory varchar(55) DEFAULT 0 NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;");

        } else {
            echo "<div class='error notice' id='adminotice'> there is some error .</div>";
        }
        //Api getting instances from vryno by using access token.
        $graphqlEndpoint = 'https://ms.vryno.dev/api/graphql/accounts';
        $query = '{
    instances(pageNumber: 1) {
        code
        status
        message
        messageKey
        data {
            id
            name
            subdomain
            created_by
        }
    }
}
';

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        );

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode(array('query' => $query)),
            'timeout' => 15,
        );


        $response = wp_remote_post($graphqlEndpoint, $args);

        //Handling response of instance api with errors
        if (is_wp_error($response)) {
            // Get the error message and escape it
            // Display an error message using WordPress functions
            wp_die(esc_html__('Something went wrong: ', 'addon-vryno-for-gravity-form') . esc_html($response->get_error_message()));
        } else {
            $responseData = wp_remote_retrieve_body($response);
            $data = json_decode($responseData, true);

            if ($data === null) {
                // Display raw response if JSON decoding fails
                if (is_array($response) || is_object($response)) {
                    // Convert the response to a JSON string for better readability
                    $response_json = wp_json_encode($response);
                    // Output the formatted response
                    echo esc_html('Invalid JSON response: ' . $response_json);
                } else {
                    // If the response is not an array or object, simply output it as is
                    echo esc_html('Invalid JSON response: ' . $response);
                }
            } elseif (isset($data['errors'])) {
                // Display GraphQL errors
                echo 'GraphQL Error: ' . wp_json_encode($data['errors']);
            } elseif (isset($data['data']['instances']['data'])) {
                //Save instances in database
                foreach ($data['data']['instances']['data'] as $instance) {
                    $id = $instance['id'];
                    $name = $instance['name'];
                    $subdomain = $instance['subdomain'];
                    $createdBy = $instance['created_by'];
                    $result_instance = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT `instance_id`, `owner_id`, `name`, `sub_domain` 
        FROM {$wpdb->prefix}tigfaddon_instance 
        WHERE `sub_domain` = %s",
                            $subdomain
                        )
                    );
                    if (empty($result_instance)) {

                        $res_instance = $wpdb->query(
                            $wpdb->prepare(
                                "INSERT INTO {$wpdb->prefix}tigfaddon_instance 
    (`name`, `sub_domain`, `owner_id`, `instance_id`) 
    VALUES(%s, %s, %s, %s)",
                                $name,
                                $subdomain,
                                $createdBy,
                                $id
                            )
                        );
                    }
                }
            } else {
                echo 'Unexpected format in GraphQL response.';
            }
        }

        //update instance in vrynotoken table
        if ($instance_id == '') {


            $result = $wpdb->get_results(
                "SELECT `sub_domain`, `owner_id` FROM {$wpdb->prefix}tigfaddon_instance"
            );
            if ($result) {
                $instance_id = $result[0]->sub_domain;
                $owner_id = $result[0]->owner_id;
            }
        } else {

            $result = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `sub_domain`, `owner_id` 
    FROM {$wpdb->prefix}tigfaddon_instance 
    WHERE `sub_domain` = %s",
                    $instance_id
                )
            );
            if ($result) {
                $instance_id = $result[0]->sub_domain;
                $owner_id = $result[0]->owner_id;
            }
        }

        $res = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}tigfaddon_vrynotoken 
    SET `vryno_server_url`=%s, `refresh_token`=%s, `client_id`=%s, `client_secret`=%s, `email`=%s, `password`=%s, `grant_type`=%s, `owner_id`=%s, `instance_id`=%s",
                $url,
                $refresh_token,
                $client_id,
                $client_secret,
                $email,
                $password,
                'refresh_token',
                $owner_id,
                $instance_id
            )
        );
        //get instace id from  database


        $result = $wpdb->get_results(
            "SELECT instance_id FROM {$wpdb->prefix}tigfaddon_vrynotoken"
        );
        if ($result) {
            $instanceId = $result[0]->instance_id;
        }

        //Api getting fields from vryno by using access token.
        $body = array(
            'query' => '{
        fetchLayout(
            filters: [{ name: "moduleName", operator: "eq", value: ["lead"] }]
        ) {
            code
            message
            status
            messageKey
            data {
                config {
                    fields {
                        __typename
                        ... on NumberField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on GeneralField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on FloatField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on RecordLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on MultiLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on MultiRecordLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on LookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                    }
                }
            }
        }
    }',
            'variables' => null
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        );

        $response = wp_remote_post(
            'https://' . $instanceId . '.ms.vryno.dev/api/graphql/crm',
            array(
                'body' => wp_json_encode($body),
                'headers' => $headers,
            )
        );

        //Handling response of fields api with errors

        if (is_wp_error($response)) {
            // Get the error message and escape it
            // Display an error message using WordPress functions
            wp_die(esc_html__('Something went wrong: ', 'addon-vryno-for-gravity-form') . esc_html($response->get_error_message()));

        } else {
            $response_body = wp_remote_retrieve_body($response);
            $responseData = json_decode($response_body, true);
            // Process $responseData as needed
        }
        $selecfieldresult = $wpdb->get_results(
            "SELECT `vrynofieldlabel`, `vrynofieldname`, `vrynofieldtype`, `vrynofieldid` 
    FROM {$wpdb->prefix}tigfaddon_vrynofields"
        );


        if (empty($selecfieldresult)) {
            $fieldsData = $responseData['data']['fetchLayout']['data'];
            // Loop through each set of fields
            foreach ($fieldsData as $fieldSet) {
                // Access the 'config' key, then 'fields' (which is an array of fields)
                $fields = $fieldSet['config']['fields'];
                // Loop through each field
                foreach ($fields as $field) {
                    // Access specific field information
                    $fieldName = $field['name'];
                    $fieldLabel = $field['label']['en'];
                    $data_type = $field['dataType'];
                    if ($field['mandatory'] == false) {
                        $mandatory = 0;
                    } else {
                        $mandatory = 1;
                    }
                    // Output or use the extracted values as needed


                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}tigfaddon_vrynofields 
    (`vrynofieldname`, `vrynofieldlabel`, `vrynofieldtype`, `vrynofieldid`, `mandatory`) 
    VALUES(%s, %s, %s, '', %d)",
                            $fieldName,
                            $fieldLabel,
                            $data_type,
                            $mandatory
                        )
                    );
                }
            }
        }
        exit;
    }

    /**
     * This function is used in a function to create a tab for TI Vryno CRM Mapping when editing any gravity form.
     * Returning menu item
     */

    public static function avgf_toolbar($menu_items, $form_id)
    {
        // Generate the nonce
        $nonce = wp_create_nonce('mappingpage_nonce');

        // Create the URL with the nonce
        $url = add_query_arg(
            array(
                'page' => 'mappingpage',
                'id' => $form_id,
                '_wpnonce' => $nonce
            ),
            self_admin_url('admin.php')
        );

        $menu_items['mapping_page_link'] = array(
            'label' => 'Vryno CRM Mapping', // the text to display on the menu for this link
            'title' => 'Vryno CRM Mapping', // the text to be displayed in the title attribute for this link
            'url' => $url, // the URL this link should point to
            'menu_class' => 'gf_form_toolbar_custom_link', // optional, class to apply to menu list item (useful for providing a custom icon)
            'link_class' => rgget('page') == 'mappingpage' ? 'gf_toolbar_active' : 'activetool', // class to apply to link (useful for specifying an active style when this link is the current page)
            'capabilities' => array('gravityforms_edit_forms'), // the capabilities the user should possess in order to access this page
            'priority' => 500 // optional, use this to specify the order in which this menu item should appear; if no priority is provided, the menu item will be append to end
        );

        return $menu_items;
    }


    /**
     * This function create a tab for Vryno CRM Mapping when editing any gravity form.
     */

    public static function avgf_create_mapping_menu($menus)
    {
        // Generate the nonce
        $nonce = wp_create_nonce('mappingpage_nonce');

        // Create the URL with the nonce
        // Create the URL with the nonce
        $url = add_query_arg(
            array(
                'page' => 'mappingpage',
                '_wpnonce' => $nonce
            ),
            self_admin_url('admin.php')
        );

        // Add the menu item with the nonce in the URL
        $menus[] = array(
            'name' => 'mappingpage',
            'label' => 'Vryno CRM Mapping',
            'callback' => array('avgf_FormsAddon', 'avgf_crm_mapping_page'),
            'permission' => 'edit_posts',
            'url' => $url  // Add the URL with nonce
        );

        return $menus;
    }

    /**
         * This function returns the mapping page. Where we are getting link to go to Gravity form and and to vryno addon setting
         
         */

    public static function avgf_crm_mapping_page()
    {
        global $wpdb;
        $Form_id = '';
        if (isset($_GET['id']))
            $Form_id = sanitize_text_field($_GET['id']);
        if (!$Form_id) {
            die("<h3>Please select gravity form's setting or edit any form to go to mapping page.</h3><h3><a href=" . esc_html(self_admin_url('admin.php?page=gf_edit_forms')) . ">Click Here</a> to get the list of Gravity Forms and click on setting or edit form.</h3><h3><a href=" . esc_html(self_admin_url('admin.php?page=Addon+Vryno+for+Gravity+Form')) . ">Click Here</a> to visit your vryno CRM connect page OR choose <u> Addon Vryno for Gravity Form Setting </u> in Gravity Form navigation. </h3>");
        }

    }
    /**
         * This function returns the mapping page. Where we are getting link to go to Gravity form and and to vryno addon setting
         
         */

    public static function avgf_mapping_page()
    {
        global $wpdb;
        $Form_id = sanitize_text_field($_GET['id']);
        if (!$Form_id) {
            die("<h3>Please select gravity form's setting or edit any form to go to mapping page.</h3><h3><a href=" . esc_html(self_admin_url('admin.php?page=gf_edit_forms')) . ">Click Here</a> to get the list of Gravity Forms and click on setting or edit form.</h3><h3><a href=" . esc_html(self_admin_url('admin.php?page=TI+Gravity+vryno+AddOn')) . ">Click Here</a> to visit your vryno CRM connect page OR choose <u> Addon Vryno for Gravity Form Setting </u> in Gravity Form navigation. </h3>");
        }
        $form = GFAPI::get_form($Form_id);
        $plugin_url = plugin_dir_url(__FILE__);
        $results = self::avgf_database_mapping_result($Form_id);
        $form_fields_mandat = self::avgf_mandatory_form();
        $form_fields = self::avgf_fetch_crm_fields();
        $gravityformfieldsarray1 = array();
        $gravityformfieldsarray2 = array();
        $gravityformfield_name = array();
        $vrynolead_field_array = array();
        // Define the URL to the stylesheet
        $style_url = $plugin_url . "/css/custom.css";

        // Define the path to the stylesheet
        $style_path = plugin_dir_path(__FILE__) . 'css/custom.css';

        // Get the file modification time
        $style_version = filemtime($style_path);

        wp_enqueue_style("custom", $style_url, array(), $style_version, 'all');

        include __DIR__ . "/templates/mapping.php";
    }


    /**
     * This function returns the mapping data that is already done and saved in tigfaddon_mapping.
     * returning data from tigfaddon_mapping
     */

    public static function avgf_database_mapping_result($Form_id)
    {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tigfaddon_mapping WHERE `gravity_form_id` = %d",
                $Form_id
            )
        );
        return $results;
    }

    /**
     * This function returns fields from tigfaddon_vrynofields which are mandatory
     * returning data from tigfaddon_vrynofields which are mandatory
     */

    public static function avgf_mandatory_form()
    {
        global $wpdb;
        $form_fields_mandat = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT vrynofieldlabel, vrynofieldname 
        FROM {$wpdb->prefix}tigfaddon_vrynofields 
        WHERE mandatory = %d",
                1
            )
        );
        return $form_fields_mandat;
    }

    /**
     * This function returns all fields from tigfaddon_vrynofields for mapping in mapping template
     * returning fields from tigfaddon_vrynofields
     */

    public static function avgf_fetch_crm_fields()
    {
        global $wpdb;
        // fetch vryno fields from database
        $form_fields = $wpdb->get_results(
            "SELECT DISTINCT vrynofieldlabel, vrynofieldname, mandatory 
        FROM {$wpdb->prefix}tigfaddon_vrynofields 
        ORDER BY vrynofieldlabel ASC"
        );

        return $form_fields;
    }

    /**
     * Installing Vryno Addon plugin will create all tables that are required for this plugin
     * Tables are:
     * tigfaddon_vrynotoken
     * tigfaddon_vrynofields
     * tigfaddon_mapping
     * tigfaddon_instance
     */

    public static function avgf_add_table()
    {
        global $wpdb;
        $table_name1 = $wpdb->prefix . 'tigfaddon_vrynotoken';
        $table_name3 = $wpdb->prefix . 'tigfaddon_vrynofields';
        $table_name4 = $wpdb->prefix . 'tigfaddon_mapping';
        $table_name5 = $wpdb->prefix . 'tigfaddon_instance';

        $sql1 = $wpdb->query("CREATE TABLE " . $table_name1 . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          vryno_server_url varchar(255) NOT NULL,
          refresh_token varchar(255) NOT NULL,
          client_id varchar(255) NOT NULL,
          client_secret varchar(255) NOT NULL,
          email varchar(255) NOT NULL,
          password varchar(255) NOT NULL,
          grant_type varchar(255) NOT NULL,
          owner_id varchar(255) NOT NULL,
          instance_id varchar(255) NOT NULL,
          PRIMARY KEY  (id)
        ) ENGINE=InnoDB;");
        //field table
        $sql3 = $wpdb->query("CREATE TABLE " . $table_name3 . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          vrynofieldname varchar(255) NOT NULL,
          vrynofieldlabel varchar(255) NOT NULL,
          vrynofieldtype varchar(255) NOT NULL,
          vrynofieldid varchar(255) NOT NULL,
          mandatory varchar(55) DEFAULT 0 NOT NULL,
          PRIMARY KEY  (id)
        ) ENGINE=InnoDB;");
        //mapping
        $sql4 = $wpdb->query("CREATE TABLE " . $table_name4 . " (id mediumint(9) NOT NULL AUTO_INCREMENT,
          gravity_form_id varchar(55) NOT NULL,
          gravityform_field_id varchar(55) NOT NULL,
           gravityformfield_name varchar(55) NOT NULL,
          vrynolead_field_id varchar(55) NOT NULL,
          enable varchar(55) DEFAULT 0 NOT NULL,          
          PRIMARY KEY  (id)
        ) ENGINE=InnoDB;");
        //instance table
        $sql5 = $wpdb->query("CREATE TABLE " . $table_name5 . " (id mediumint(9) NOT NULL AUTO_INCREMENT,
        instance_id varchar(255) NOT NULL,
        owner_id varchar(255) NOT NULL,
        name varchar(255) NOT NULL,
        sub_domain varchar(255) NOT NULL,         
        PRIMARY KEY  (id)
      ) ENGINE=InnoDB;");

    }

    /**
     * Uninstalling Vryno Addon plugin will delete all tables that are created for this plugin
     * Tables are:
     * tigfaddon_vrynotoken
     * tigfaddon_vrynofields
     * tigfaddon_mapping
     * tigfaddon_instance
     */

    public static function avgf_uninstall_hook()
    {
        global $wpdb;
        $table_name1 = $wpdb->prefix . 'tigfaddon_vrynotoken';
        $table_name3 = $wpdb->prefix . 'tigfaddon_vrynofields';
        $table_name4 = $wpdb->prefix . 'tigfaddon_mapping';
        $table_name5 = $wpdb->prefix . 'tigfaddon_instance';
        $table_name1 = esc_sql($table_name1); // Sanitize the table name
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name1));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name3));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name4));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name5));

    }

    /**
     * Here Refreshing fields of Vryno
     * @param $refresh_token     Argument provided in the post
     * @param $client_id         Argument provided in the post
     * @param $client_secret     Argument provided in the post
     * @param $vryno_server_url  Argument provided in the post
     * This function handles two apis of vryno. 
     * In First getting access token from vryno by sending above params.
     * In Second getting fields from vryno. So that mapping of vryno fields can be done with gravity form fields.
     */

    public static function avgf_refresh_fields()
    {
        global $wpdb;
        // Verify the nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'my-ajax-nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        // Getting vryno token for Module
        // Check if $_POST['refresh_token'] is set and not empty
        if (isset($_POST['refresh_token']) && !empty($_POST['refresh_token'])) {
            // Sanitize and validate the refresh_token
            $sanitized_refresh_token = sanitize_text_field(wp_unslash($_POST['refresh_token']));

            // Escape the sanitized refresh_token for safe use in queries or output
            $refresh_token = esc_sql($sanitized_refresh_token);

            // Now you can use $refresh_token in your queries or output
        } else {
            wp_send_json_error('Refresh token is not valid');
        }
        // Check if $_POST['client_id'] is set and not empty
        if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
            // Sanitize and validate the client_id
            $sanitized_client_id = sanitize_text_field(wp_unslash($_POST['client_id']));

            // Escape the sanitized client_id for safe use in queries or output
            $client_id = esc_sql($sanitized_client_id);

            // Now you can use $client_id in your queries or output
        } else {
            wp_send_json_error('Client Id is not valid');
        }
        // Check if $_POST['client_secret'] is set and not empty
        if (isset($_POST['client_secret']) && !empty($_POST['client_secret'])) {
            // Sanitize and validate the client_secret
            $sanitized_client_secret = sanitize_text_field(wp_unslash($_POST['client_secret']));

            // Escape the sanitized client_secret for safe use in queries or output
            $client_secret = esc_sql($sanitized_client_secret);

            // Now you can use $client_secret in your queries or output
        } else {
            wp_send_json_error('Client Secret is not valid');
        }
        // Sanitize, validate, and escape the $_POST['vryno_server_url']
        // Retrieve the value from POST and sanitize it using WordPress functions
        $vryno_server_url = isset($_POST['vryno_server_url']) ? esc_url_raw(trim($_POST['vryno_server_url'])) : '';

        // Validate the URL (optional, depending on use case)
        if (empty($vryno_server_url) || !filter_var($vryno_server_url, FILTER_VALIDATE_URL)) {
            // Handle invalid URL
            $vryno_server_url = ''; // or set to a default value, or show an error
        }


        $lastChar = strlen($vryno_server_url) - 1;
        if ($vryno_server_url[$lastChar] != "/") {
            $url = $vryno_server_url;
        } else {
            $url = rtrim($vryno_server_url, '/');
        }
        $response = wp_remote_post(
            $url,
            array(
                'method' => 'POST',
                'timeout' => 45,
                'headers' => array(),
                'body' => array(
                    'refresh_token' => $refresh_token,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type' => 'refresh_token'
                ),
            )
        );
        $tokenresult = $response['body'];
        $token = json_decode($tokenresult)->access_token;
        $error = json_decode($tokenresult)->error;
        if (!empty($token)) {
            echo "<div class='updated notice' id='adminotice'>Your token has been successfully generated.</div>";
            // Drop and recreate table
            $table_tigfaddon_vrynofields = $wpdb->prefix . 'tigfaddon_vrynofields';
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_tigfaddon_vrynofields));

            $wpdb->query(
                $wpdb->prepare(
                    "CREATE TABLE %s " . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vrynofieldname varchar(255) NOT NULL,
            vrynofieldlabel varchar(255) NOT NULL,
            vrynofieldtype varchar(255) NOT NULL,
            vrynofieldid varchar(255) NOT NULL,
            mandatory varchar(55) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id)
    ) ENGINE=InnoDB;",
                    $table_tigfaddon_vrynofields
                )
            );

            //get instace id from  database
            $instanceId = "";
            $result = $wpdb->get_results(
                "SELECT `instance_id` 
    FROM {$wpdb->prefix}tigfaddon_vrynotoken"
            );

            if ($result) {
                $instanceId = $result[0]->instance_id;
            }
            // Curl start and get vryno fields
            $body = array(
                'query' => '{
        fetchLayout(
            filters: [{ name: "moduleName", operator: "eq", value: ["lead"] }]
        ) {
            code
            message
            status
            messageKey
            data {
                config {
                    fields {
                        __typename
                        ... on NumberField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on GeneralField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on FloatField {
                            label { en }
                            name
                            uniqueName
                            mandatory
                            dataType
                        }
                        ... on RecordLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on MultiLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on MultiRecordLookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                        ... on LookupField {
                            label { en }
                            name
                            uniqueName
                            dataType
                        }
                    }
                }
            }
        }
    }',
                'variables' => null
            );

            $headers = array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            );

            $args = array(
                'body' => wp_json_encode($body),
                'headers' => $headers,
            );

            $response = wp_remote_post('https://' . $instanceId . '.ms.vryno.dev/api/graphql/crm', $args);

            if (is_wp_error($response)) {
                // Get the error message and escape it
                // Display an error message using WordPress functions
                wp_die(esc_html__('Something went wrong: ', 'addon-vryno-for-gravity-form') . esc_html($response->get_error_message()));
            } else {
                $response_body = wp_remote_retrieve_body($response);
                $responseData = json_decode($response_body, true);

                // Check for JSON decoding errors
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Get the JSON decoding error message and sanitize it
                    // Output the sanitized error message
                    die('Error decoding JSON: ' . esc_html(json_last_error_msg()));
                }

                // Process $responseData as needed
            }

            $fieldsData = $responseData['data']['fetchLayout']['data'];
            // Loop through each set of fields
            foreach ($fieldsData as $fieldSet) {
                // Access the 'config' key, then 'fields' (which is an array of fields)
                $fields = $fieldSet['config']['fields'];
                // Loop through each field
                foreach ($fields as $field) {
                    // Access specific field information
                    $fieldName = $field['name'];
                    $fieldLabel = $field['label']['en'];
                    $data_type = $field['dataType'];
                    $system_mandatory = ($field['mandatory'] == false) ? 0 : 1;
                    // Output or use the extracted values as needed


                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}tigfaddon_vrynofields 
    (`vrynofieldname`, `vrynofieldlabel`, `vrynofieldtype`, `vrynofieldid`, `mandatory`) 
    VALUES(%s, %s, %s, '', %d)",
                            $fieldName,
                            $fieldLabel,
                            $data_type,
                            $system_mandatory
                        )
                    );
                }
            }
        } else {
            echo "<div class='error notice' id='adminotice'>There is some error.</div>";
        }
    }

    /**
     * Here Mapping of  gravity form fields with Vryno fields and inserting fields in tigfaddon_mapping table
     */

    public static function avgf_update_mapping()
    {
        global $wpdb;

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'my-ajax-nonce')) {
            wp_send_json_error('Nonce verification failed');
        }

        // Sanitize, validate, and escape $_POST data
        $formarrays = array_map(function ($v) {
            return esc_html(sanitize_text_field(wp_unslash($v)));
        }, wp_unslash($_POST['data']));

        $datajson = wp_json_encode($formarrays);

        $formarray = json_decode($datajson, 1);

        // Sanitize $_POST['formid']
        $form_id = isset($_POST['formid']) ? intval(sanitize_text_field($_POST['formid'])) : '';


        // Validate if necessary
        if (!empty($formid)) {
            // Example validation: Check if $formid is numeric
            if (!is_numeric($formid)) {
                wp_send_json_error('Form Id is not valid');
            }
        }
        $x = 0;
        //Check if form id already enabled 

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tigfaddon_mapping WHERE `gravity_form_id` = %s",
                $form_id
            )
        );

        foreach ($results as $data) {
            $temp[] = $data->gravity_form_id;
            $enable = $data->enable;
        }
        $select = [];
        $field = [];

        if (is_array($results) && empty($results)) {

            foreach ($formarray as $key => $value) {

                $exp_key = explode('_', $key);
                if ($exp_key[0] == 'field') {
                    $arr_result['form_field_id'] = $value;
                    foreach ($arr_result as $field[]) {
                    }
                }
                if ($exp_key[0] == 'select') {
                    $array_select['select_field_id'] = $value;
                    foreach ($array_select as $select[]) {
                    }
                }
            }

            $vryno_f_field = array();
            $vryno_f_field = array_combine($select, $field);
            $vryno_f_field11 = array_combine($field, $select);

            $duplicatsval = self::avgf_has_dupes($vryno_f_field11);
            foreach ($vryno_f_field11 as $vrynoformfieldsid => $gformfieldsid) {
                $vrynoformfieldsids[] = $vrynoformfieldsid;
                $gffieldsname = $formarrays[$x];
                foreach ($vrynoformfieldsids as $vrynoformfield) {

                    $vrynofields = explode('_', $vrynoformfield);
                    $gffieldsid = $vrynofields[0];
                }

                $result = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}tigfaddon_mapping 
    (`gravity_form_id`, `gravityform_field_id`, `gravityformfield_name`, `vrynolead_field_id`, `enable`) 
    VALUES(%s, %s, %s, %s, %d)",
                        $form_id,
                        $gffieldsid,
                        $gffieldsname,
                        $gformfieldsid,
                        1
                    )
                );
                $x++;
            }
            echo "<div class='updated notice' id='adminotice'>Mapped successfully</div>";
        } else {
            $arr_result = array();
            $arr_result_select = array();
            $arr_select = array();

            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}tigfaddon_mapping WHERE `gravity_form_id` = %s",
                    $form_id
                )
            );
            foreach ($formarray as $key => $value) {
                $exp_key = explode('_', $key)[0];
                if ($exp_key == "field") {
                    array_push($arr_result, $value);
                }
                if ($exp_key == "select") {
                    array_push($arr_select, $value);
                }
                $exp_keys = explode('_', $value)[0];
                if ($exp_keys == "select") {
                    array_push($arr_result_select, $value);
                }
            }
            foreach ($arr_select as $keysss => $arr) {
                $seachkey = array_search($arr, $formarray);
                $finalkey = array_search($seachkey, $formarray);
                $arr_selectnew[$finalkey] = $arr;
            }
            for ($i = 0; $i <= count($arr_result) - 1; $i++) {
                if (array_key_exists($i, $arr_selectnew)) {
                    $newArray1[$i] = $arr_selectnew[$i];
                } else {
                    $newArray2[$i] = $arr_selectnew[$i];
                }
            }
            if ($newArray1 == $arr_selectnew) {
                $systemArray = $arr_selectnew;
            }
            if (!empty($newArray1) && !empty($newArray2)) {
                $arrayadd = $newArray1 + $newArray2;
                ksort($arrayadd);
            }
            foreach ($arrayadd as $xkey => $x_value) {
                $systemArray[$xkey] = $x_value;
            }
            $vryno_f_field = array();
            $vryno_f_field = array_combine($select, $field);
            if ($systemArray != NULL && $arrayadd !== NULL) {
                $vryno_f_field11 = array_combine($arr_result, $arrayadd);
            } elseif ($arrayadd !== NULL) {
                $vryno_f_field11 = array_combine($arr_result, $arrayadd);
            } else {
                $vryno_f_field11 = array_combine($arr_result, $systemArray);
            }
            $duplicatsval = self::avgf_has_dupes($vryno_f_field11);
            foreach ($vryno_f_field11 as $vrynoformfieldsid => $gformfieldsid) {
                $vrynoformfieldsids[] = $vrynoformfieldsid;
                $gffieldsname = $formarrays[$x];
                foreach ($vrynoformfieldsids as $vrynoformfield) {
                    $vrynofields = explode('_', $vrynoformfield);
                    $gffieldsid = $vrynofields[0];
                }


                $result = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO " . $wpdb->prefix . "tigfaddon_mapping" . " (`gravity_form_id`,`gravityform_field_id`,`gravityformfield_name`,`vrynolead_field_id`,`enable`) 
     VALUES (%d, %d, %s, %d, %d)",
                        $form_id,
                        $gffieldsid,
                        $gffieldsname,
                        $gformfieldsid,
                        1
                    )
                );

                $x++;
            }
            echo "<div class='updated notice' id='adminotice'>mapping has been updated successfully</div>";
        }
    }

    public static function avgf_has_dupes($array)
    {
        $dupe_array = array();
        foreach ($array as $val) {
            if ($val != "select") {
                if (++$dupe_array[$val] > 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Here Deleting Mapping of gravity form fields with Vryno fields and Deleting entries from tigfaddon_mapping table
     */

    public static function avgf_delete_mapping()
    {
        global $wpdb;
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'my-ajax-nonce')) {
            wp_send_json_error('Nonce verification failed');
        }
        // Sanitize $_POST['formid']
        $formid = isset($_POST['formid']) ? sanitize_text_field($_POST['formid']) : '';

        // Validate if necessary
        if (!empty($formid)) {
            // Example validation: Check if $formid is numeric
            if (!is_numeric($formid)) {
                wp_send_json_error('Form Id is not valid');
            }
        }

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tigfaddon_mapping WHERE `gravity_form_id` = %s",
                $formid
            )
        );
        if ($result === false) {
            echo "<div class='error notice' id='adminotice'>Try Again!</div>";
        } else {
            echo "<div class='error notice' id='adminotice'>Deleted successfully !</div>";
        }

    }
}
?>