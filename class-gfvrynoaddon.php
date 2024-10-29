<?php
if (!defined('ABSPATH'))
    exit;
GFForms::include_addon_framework();

/**
 * Class AVGF_VrynoAddOn
 *
 * Facilitates the creation of the Gravity Forms Addon with Vryno 
 *
 */

class AVGF_VrynoAddOn extends GFAddOn
{
    protected $_version = '1.0';
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'Addon Vryno for Gravity Form';
    protected $_path = 'addonVrynoForGravityForm/gravityformaddon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Addon Vryno for Gravity Form Setting';
    protected $_short_title = 'Addon Vryno for Gravity Form Setting';
    private static $_instance = null;
    public static function avgf_vryno_load()
    {
        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }
        GFAddOn::register('AVGF_VrynoAddOn');
    }
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new AVGF_VrynoAddOn();
        }
        return self::$_instance;
    }

    public function init()
    {
        parent::init();
        //On submitting gravity form it will pass data in avgf_gravity_post_to_vryno function
        add_filter('gform_after_submission', array($this, 'avgf_gravity_post_to_vryno'), 10, 2);
    }

    public function plugin_page()
    {
        //including html template file
        global $wpdb;
        //getting data from tigfaddon_vrynotoken to pass it into template
        $table_name = $wpdb->prefix . 'tigfaddon_vrynotoken';
        $table_name = esc_sql($table_name); // Sanitize the table name
        $query = "SELECT `vryno_server_url`, `refresh_token`, `client_id`, `client_secret`, `grant_type`, `email`, `password`, `instance_id` FROM $table_name";

        // Execute the query
        $select = $wpdb->get_results($query);
        if (!empty($select)) {
            $vryno_server_url = $select[0]->vryno_server_url;
            $refresh_token = $select[0]->refresh_token;
            $client_id = $select[0]->client_id;
            $client_secret = $select[0]->client_secret;
            $email = $select[0]->email;
            $instance_id = $select[0]->instance_id;
        }
        //getting instances from tigfaddon_instance to pass it into template
        $instanceselect = $wpdb->get_results("SELECT `sub_domain`  FROM " . $wpdb->prefix . 'tigfaddon_instance');
        if (!empty($instanceselect)) {
            foreach ($instanceselect as $instance) {
                $instancearr[] = $instance->sub_domain;
            }
        }
        $plugin_url = plugin_dir_url(__FILE__);
        // Define the URL to the script file
        $script_url = $plugin_url . "/js/my-validation-script.js";

        // Define the path to the script file
        $script_path = plugin_dir_path(__FILE__) . 'js/my-validation-script.js';

        // Get the file modification time
        $script_version = filemtime($script_path);
        // Retrieve the current request URI
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(trim($_SERVER['REQUEST_URI'])) : '';
        wp_register_script('myvalidation', $script_url, array('jquery'), $script_version, false);
        wp_enqueue_script('myvalidation');
        wp_localize_script(
            'myvalidation',
            'myScript',
            array(
                'ajax_url' => $request_uri
            )
        );
        wp_enqueue_script('myvalidation');
        // Define the URL to the stylesheet
        $style_url = $plugin_url . "/css/custom.css";

        // Define the path to the stylesheet
        $style_path = plugin_dir_path(__FILE__) . 'css/custom.css';

        // Get the file modification time
        $style_version = filemtime($style_path);

        wp_enqueue_style("custom", $style_url, array(), $style_version, 'all');

        include __DIR__ . "/templates/vrynoconnect.php";
    }

    /**
     * Here posting Data to Vryno 
     * @param $entry     Argument provided in the post
     * @param $form      Argument provided in the post
     * This function handles create lead api of vryno
     */

    function avgf_gravity_post_to_vryno($entry, $form)
    {
        $plugin_url = plugin_dir_url(__FILE__);
        global $wpdb;// Make sure $wpdb is accessible
        $formid = isset($entry['form_id']) ? sanitize_text_field($entry['form_id']) : '';

        // Validate if necessary
        if (!empty($formid)) {
            // Example validation: Check if $formid is numeric
            if (!is_numeric($formid)) {
                wp_send_json_error('Form Id is not valid');
            }
        }



        // Execute the prepared query and get results
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vrynolead_field_id, gravityform_field_id, enable 
    FROM {$wpdb->prefix}tigfaddon_mapping 
    WHERE gravity_form_id = %d",
                $formid
            )
        );


        if (!empty($results)) {
            foreach ($results as $data) {
                $vrynolead_field_id[] = $data->vrynolead_field_id;
                $gravityform_field_id[] = $data->gravityform_field_id;
                $enable = $data->enable;
                foreach ($gravityform_field_id as $key => $value) {
                }
                if (array_key_exists($value, $entry)) {
                    $Data[] = $entry[$value];

                } else if (!array_key_exists($value, $entry) && in_array($value, $gravityform_field_id)) {
                    $Data[] = "";
                }
            }
            $datacount = count($Data);
            $vrynofielscount = count($vrynolead_field_id);
            if ($datacount > $vrynofielscount) {
                $loaddata = $datacount - $vrynofielscount;
                for ($i = 1; $i <= $loaddata; $i++) {
                    array_push($vrynolead_field_id, "");
                }
            } else if ($datacount < $vrynofielscount) {
                $loaddata = $vrynofielscount - $datacount;
                for ($i = 1; $i <= $loaddata; $i++) {
                    array_push($Data, "you can only map single line data field");
                }
            }
            // takes two arrays and creates a new array where the first array's values become the keys,
            // and the second array's values become the values
            $vrynodata = array_combine($vrynolead_field_id, $Data);
            $vrynodatanew = array("data" => array($vrynodata));

            $result = $wpdb->get_results("SELECT `vryno_server_url`, `refresh_token`, `client_id`, `client_secret`, `email`, `password`, `grant_type`, `instance_id`, `owner_id` FROM " . $wpdb->prefix . "tigfaddon_vrynotoken");

            // Getting vryno token for Module
            $refresh_token = $result[0]->refresh_token;
            $client_id = $result[0]->client_id;
            $client_secret = $result[0]->client_secret;
            $vryno_server_url = $result[0]->vryno_server_url;
            $instanceId = $result[0]->instance_id;
            $owner_id = $result[0]->owner_id;
            // Generating new token to insert vryno leads
            $lastChar = strlen($vryno_server_url) - 1;
            if ($vryno_server_url[$lastChar] != "/") {
                $url = $vryno_server_url;
            } else {
                $url = rtrim($vryno_server_url, '/');
            }
            //getting access token using vryno api
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
            $tokenresult = json_decode($response['body']);
            $token = '';
            $error = '';
            if (isset($tokenresult->access_token))
                $token = $tokenresult->access_token;
            if (isset($tokenresult->error))
                $error = $tokenresult->error;

            if (!empty($token)) {
            } else if (!empty($error)) {
                echo '<p> there is an error in generating token {error: ' . esc_html($error) . '} </p>';

            } else {
                echo "<p> there is some error .</p>";
            }

            //Hitting create lead api
            $apiUrl = "https://" . $instanceId . ".ms.vryno.dev/api/graphql/crm";
            // Assuming the first element of the 'data' array contains the desired key-value pairs
            $data = $vrynodatanew['data'][0];
            // Extracting keys and values
            $keys = array_keys($data);
            $values = array_values($data);
            // Adding ownerId to the array
            $keys[] = 'ownerId';
            $values[] = $owner_id;
            // Constructing the GraphQL mutation
            // Construct the mutation string
            $mutation = 'mutation {
    createLead(input: {';

            // Add key-value pairs to the mutation
            for ($i = 0; $i < count($keys); $i++) {
                // If the value is a string, wrap it in double quotes
                $value = is_string($values[$i]) ? '"' . esc_js($values[$i]) . '"' : esc_js($values[$i]);
                // Add the key-value pair to the mutation
                $mutation .= esc_js($keys[$i]) . ': ' . $value;
                // Add a comma if it's not the last pair
                if ($i < count($keys) - 1) {
                    $mutation .= ', ';
                }
            }

            // Complete the mutation string
            $mutation .= '}) {
    code
    message
    status
    messageKey
    data {
        name
        id
        phoneNumber
    }
}
}';

            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field($token)
            );

            $args = array(
                'body' => wp_json_encode(array('query' => $mutation)),
                'headers' => $headers,
                'method' => 'POST',
                'data_format' => 'body'
            );

            // Send the request using wp_remote_post
            $response = wp_remote_post($apiUrl, $args);



            // Check for errors
            if (is_wp_error($response)) {
                // Get the error message and escape it
                $error_message = $response->get_error_message();
                // Display an error message using WordPress functions
                wp_die(esc_html__('Error occurred: ', 'addon-vryno-for-gravity-form') . esc_html($error_message));
            }

            // Decode the JSON response
            $result = json_decode(wp_remote_retrieve_body($response), true);

            // Display result
            if (isset($result['data']['createLead']['message'])) {
                // echo esc_html($result['data']['createLead']['message']);

            } else {
                //  echo esc_html__('Message not found in the response.', 'addon-vryno-for-gravity-form');

            }

        }



    }
}