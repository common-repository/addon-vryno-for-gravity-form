<?php
/*
 * The file is using to manage the mapping form and that data.
 */

if (!defined('ABSPATH'))
    exit;


$extraFieldfound = array();
$selectarray = [];
if (!empty($results)) {
    foreach ($results as $fieldsvalue) {
        $vrynolead_field_array[] = $fieldsvalue->vrynolead_field_id;
        $gravityformfield_name[] = $fieldsvalue->gravityformfield_name;
    }
    echo "<h2 id='title'> Form Mapping </h2>";
} else {
    echo "<h2 id='title'> This form is not mapped, Please map the below form. </h2>";
}
foreach ($form_fields_mandat as $vrynomandat) {
    $mandatforvryno[] = $vrynomandat->vrynofieldname;
    $mandatFName[] = $vrynomandat->vrynofieldlabel;
}
function avgf_addtwoarray($arrayfirst, $arraysecond)
{
    $final = $arrayfirst + $arraysecond;
    return $final;
}
?>
<div class="gform_panel" id="vryno_form_page">
    <div class="row">
        <div class="gaddon-section gaddon-first-section">
            <div class="mappingloader" style="display: none;">Loading...</div>
            <div id="form_map" name="form_mapped">
                <table class="form-table">
                    <tbody>
                        <?php
                        foreach ($form['fields'] as $key => $field) {

                            $isRequired = $field['isRequired'];

                            if ($field["type"] != "captcha" && $field["type"] != "checkbox") {

                                if (isset($field['inputs'])) {

                                    if (!empty($field->inputs)) {
                                        foreach ($field->inputs as $gravityformfeild) {

                                            if (!isset($gravityformfeild['isHidden']) || ($gravityformfeild['isHidden'] != 1)) {

                                                //if($gravityformfeild['isHidden'] != 1){
                                                array_push($gravityformfieldsarray1, array("id" => $gravityformfeild['id'], "label" => $gravityformfeild['label'], "required" => $field['isRequired']));
                                                // }
                                            }
                                        }
                                    } else {

                                        if (!empty($field->label) && ($field->isHidden != 1)) {
                                            array_push($gravityformfieldsarray2, array("id" => $field->id, "label" => $field->label, "required" => $field['isRequired']));
                                        }
                                    }
                                } else {

                                    if (!empty($field->label) && ($field->isHidden != 1)) {
                                        array_push($gravityformfieldsarray2, array("id" => $field->id, "label" => $field->label, "required" => $field['isRequired']));
                                    }
                                }
                            }
                        }
                        $formfields = array_merge($gravityformfieldsarray1, $gravityformfieldsarray2);
                        //print_r($formfields);
                        foreach ($formfields as $key => $field) {
                            $f_names = $field['label'];
                            $isRequired = $field['required'];
                            if ($isRequired == 1) {
                                $required = "required";
                                $requiredsign = "*";
                            } else {
                                $required = "";
                                $requiredsign = "";
                            }
                            if ($f_names == "Name:") {
                                $f_name = "Name";
                            } else {
                                $f_names = preg_replace('/[^\p{L}\p{N}\s]/u', '', $field['label']);
                                $explodedval = explode(" ", $f_names);
                                if (count($explodedval) > 1) {
                                    $f_nameimp = implode("_", $explodedval);
                                    $f_name = $f_nameimp;
                                } else {
                                    $f_name = $f_names;
                                }

                            }
                            $f_id = $field['id'];
                            $selectarray[] = 'select_' . $f_name;
                            $fieldataraay[] = "field_id_" . $f_name;
                            $selandfieldarray = array_combine($fieldataraay, $selectarray);
                            $label = str_replace("_", " ", $f_name);
                            ?>
                            <tr valign="top" id="label">
                                <th scope="row">
                                    <label for="<?php echo esc_attr('select_' . $f_name) ?>"
                                        class="control-label vrynofieldlabel">
                                        <?php echo esc_html($label) . "<span style='color:red;'>" . esc_html($requiredsign) . "</span>"; ?>
                                    </label>
                                </th>
                                <th scope="row">
                                    <input type="hidden" id="<?php echo esc_attr($f_name . '_field_id') ?>"
                                        name="<?php echo esc_attr("field_id_" . $f_name) ?>"
                                        value="<?php echo esc_attr($f_id) ?>">
                                </th>
                                <?php
                                foreach ($selectarray as $seleckey => $selectval) {
                                    ?>
                                    <input type="hidden" name="<?php echo esc_attr($seleckey) ?>"
                                        value="<?php echo esc_attr($selectval) ?>" />
                                    <?php
                                }

                                if (empty($results)) {
                                    $display = "none;";
                                    $seldisplay = "visible;";
                                } else if (!empty($results)) {
                                    $display = "visible;";
                                    $seldisplay = "none;";
                                    ?>
                                        <td id="selectinput">
                                            <input type="text" id="<?php echo esc_attr("input_select_" . $f_name) ?>" value=""
                                                disabled />
                                            <input type="hidden" name="<?php echo esc_attr($Form_subview) ?>"
                                                value="<?php echo esc_attr($Form_id) ?>">
                                        </td>
                                    <?php
                                }
                                ?>
                                <td id="select" style="display: <?php echo esc_attr($seldisplay) ?>">
                                    <select class="form-control selectformfield"
                                        id="<?php echo esc_attr("select_" . $f_name) ?>"
                                        name="<?php echo esc_attr("select_" . $f_name) ?>" value="" <?php echo esc_attr($required) ?>>
                                        <option value="">--Select--</option>
                                        <?php
                                        foreach ($form_fields as $formfield) {
                                            $db_form_field = $formfield->vrynofieldname;
                                            $form_field_name = $formfield->vrynofieldlabel;
                                            if ($db_form_field != "Lead_Source") {
                                                echo '<option id="' . esc_attr($db_form_field) . '" value="' . esc_attr($db_form_field) . '">' . esc_html($form_field_name) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <input type="hidden" name="<?php echo esc_attr($Form_subview) ?>" id="FormId"
                                    value="<?php echo esc_attr($Form_id) ?>">
                            </tr>
                            <?php
                        }

                        if (count($selectarray) > count($gravityformfield_name) && (!empty($gravityformfield_name))) {
                            $withoutDuplicates = array_unique(array_diff_assoc($selectarray, array_unique($selectarray)));
                            if (!$withoutDuplicates) {

                                echo '<center>
                                     <div id="gf_popup_box">
                                      <input type="hidden" id="cancel_button" value="X">
                                      <center><h4 class="modal-title">Notice</h4></center>
                                      <p id="info_text">Please do the mapping again as you added new field in your form.
                                      </p>
                                      <button type="button" class="btn btn-danger" id="modaldeletemapping">Delete mapping</button>
                                     </div>
                                </center>';
                            }
                        }

                        if (count($selectarray) > count($gravityformfield_name)) {
                            $withoutDuplicates = array_unique(array_diff_assoc($selectarray, array_unique($selectarray)));
                            if ($withoutDuplicates != "" || $withoutDuplicates) {
                                foreach ($withoutDuplicates as $duplicate) {
                                    $duplicatefval = explode("select_", $duplicate)[1];

                                    echo '<!-- Modal -->
                                <center>
                                     <div id="gf_popup_box">
                                      <input type="button" id="cancel_button" value="X">
                                      <center><h4 class="modal-title">Duplicate field found!</h4></center>
                                      <p id="info_text">Please remove duplicate field "' . esc_html($duplicatefval) . '"
                                      </p><input type="button" id="close_button" value="Close">
                                     </div>
                                </center>';

                                }
                                $selectarray = array_unique($selectarray);
                            } else {
                                $selectarray = $selectarray;
                                $withoutDuplicates = array();
                            }
                        } else {
                            $withoutDuplicates = array();
                            $extraFieldfound = array_diff($gravityformfield_name, $selectarray);
                            if ($extraFieldfound != "" || !empty($extraFieldfound)) {
                                foreach ($extraFieldfound as $extra) {
                                    $extrafval = explode("select_", $extra)[1];

                                    echo '<!-- Modal -->
                                    <center>
                                         <div id="gf_popup_box">
                                          <input type="hidden" id="cancel_button" value="X">
                                          <center><h4 class="modal-title">Extra field found!</h4></center>
                                          <p id="info_text">Please delete current mapping as you removed "' . esc_html($extrafval) . '" field, that was already mapped! 
                                          </p>
                                          <button type="button" class="btn btn-danger" id="modaldeletemapping">Delete mapping</button>
                                         </div>
                                    </center>';

                                }
                            } else {
                                $extraFieldfound = array();
                            }
                        }


                        foreach ($gravityformfield_name as $keysss => $gravityformfield_nameval) {
                            if (count($selectarray) > count($gravityformfield_name)) {
                                $keyofarray = array_search($gravityformfield_nameval, $selectarray);
                                $vrynolead_field_array[$keyofarray] = $vrynolead_field_array[$keysss];
                                unset($vrynolead_field_array[$keysss]);
                            } else if (count($selectarray) == count($gravityformfield_name)) {
                                $selectvaluearry = array_combine($selectarray, $vrynolead_field_array);
                            } else {
                                //array_push($selectarray,"");
                                $vrynolead_field_array;
                            }
                        }

                        if (count($gravityformfield_name) > count($selectarray)) {
                            $count = count($gravityformfield_name) - 1;
                        } else {
                            $count = count($selectarray) - 1;
                        }

                        for ($i = 0; $i <= $count; $i++) {
                            if (array_key_exists($i, $vrynolead_field_array)) {
                                $newArray1[$i] = $vrynolead_field_array[$i];
                            } else {
                                if (array_key_exists($i, $vrynolead_field_array)) {
                                    $newArray2[$i] = $vrynolead_field_array[$i];
                                }
                            }
                        }
                        if (!empty($newArray2) && !empty($newArray1)) {
                            $newFieldArray = avgf_addtwoarray($newArray1, $newArray2);

                            //$newFieldArray = $newArray1 + $newArray2;
                            ksort($newFieldArray);

                            foreach ($newFieldArray as $xkey => $x_value) {
                                $systemArray[$xkey] = $x_value;
                            }
                        } else {
                            $systemArray = $vrynolead_field_array;
                        }

                        if (!empty($selectarray) && !empty($systemArray)) {
                            $selectvaluearry = array_combine($selectarray, $systemArray);
                        } else {
                            $selectvaluearry = array("no data");
                        }

                        if ($selectvaluearry == false) {
                            $selectvaluearry = array("no data");
                        }
                        ?>
                        <tr id="tr-button" valign="top">
                            <td id="tigformtd">
                                <button type="submit" class="button-primary gfbutton gaddon-setting gaddon-submit"
                                    id="tigform-settings" style="display: <?php echo esc_attr($seldisplay) ?>">Update
                                    Setting</button>
                                <a class="button-save" id="gform-settings-edit"
                                    style="display: <?php echo esc_attr($display) ?>">Edit Mapping</a>
                                <a class="button-update-mapping" id="deletemapping"
                                    style="display: <?php echo esc_attr($display) ?>">Delete mapping</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="gaddon-section gaddon-second-section">
            <table class="gaddon-section gaddon-second-section" id="mandatory">
                <tbody>
                    <ul>
                        <?php
                        foreach ($form_fields as $formfield) {
                            $db_form_field = $formfield->vrynofieldname;
                            $form_field_name = $formfield->vrynofieldlabel;
                            $mandatory = $formfield->mandatory;

                            if ($mandatory == 1) {
                                echo '<h4 id="note"><b>NOTE :- Mandatory fields for vryno Lead Module :</b></h4>';
                                echo '<li><b><span>&#8718; &nbsp;</span>' . esc_html($form_field_name) . '(' . esc_html($db_form_field) . ')</b><li>';
                            }
                        }
                        ?>
                    </ul>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
if ($extraFieldfound == null) {
    $extraFieldfound = array();
}

// Define the URL to the script file
$script_url = $plugin_url . "/js/customscript.js";

// Define the path to the script file
$script_path = $plugin_url . 'js/customscript.js';

// Get the file modification time
$script_version = md5_file($script_path);

wp_enqueue_script('customscript', $script_url, array('jquery'), $script_version, false);

// Then localize it
wp_localize_script(
    'customscript',
    'custom_script_vars',
    array(
        'nonce' => wp_create_nonce('my-ajax-nonce'),
        'selectarray' => array_map('sanitize_text_field', $selectarray),
        'mandatarray' => array_map('sanitize_text_field', $mandatforvryno),
        'mandatFName' => array_map('sanitize_text_field', $mandatFName),
        'selandfieldarray' => array_map('sanitize_text_field', $selandfieldarray),
        'selectvaluearry' => array_map('sanitize_text_field', $selectvaluearry),
        'withoutDuplicates' => array_map('sanitize_text_field', $withoutDuplicates),
        'extraFieldfound' => array_map('sanitize_text_field', $extraFieldfound),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'Form_id' => esc_html($Form_id)
    )
);

?>