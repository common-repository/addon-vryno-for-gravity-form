document.getElementById("tigform-settings").addEventListener("click", function (e) {
    // Access PHP variables via the localized object
    var nonce = custom_script_vars.nonce;
      var formid = jQuery("#FormId").val();
    var selectarray = custom_script_vars.selectarray;
    var mandatarray = custom_script_vars.mandatarray;
    var mandatFName = custom_script_vars.mandatFName;
    var selandfieldarray = custom_script_vars.selandfieldarray;
var i = 0;
        var dataarray = [];
        var sentence = mandatFName.join(" ");
        var arrdata = {};
        var checkreqfield = [];
        var requiredfocus = [];
        console.log(selandfieldarray);
        console.log('arrdata',arrdata);
        jQuery.each(selandfieldarray, function (key, valuese) {
            var selectopval = jQuery("#" + valuese).val();
            var fieldname = valuese.split('_')[1] + "_field_id";
            var fieldnametosend = key;
            var fieldidval = jQuery("input[name=" + key + "]").val();
            var selval = jQuery("#" + valuese).val();
          

            arrdata[fieldnametosend] = fieldidval;
            arrdata[valuese] = selval;
            arrdata[i] = valuese;
     
            //checkreqfield.push(valuese);
            if (!jQuery("#" + valuese).prop('required')) {
                //do nothing
            } else {
                if (jQuery("#" + valuese).val() === "") {
                    checkreqfield.push(valuese);
                }
            }
            dataarray.push(selectopval);
            i++;
        });

        const mandatory_exists = dataarray.some((val) => mandatarray.indexOf(val) !== -1);
        console.log(mandatory_exists);
        let findDuplicates = arr => arr.filter((item, index) => arr.indexOf(item) != index)
        var newduplicate = [...new Set(findDuplicates(dataarray))];
        if (newduplicate[1] != "") {
            var dupval = newduplicate[1];
        } else {
            var dupval = newduplicate[0];
        }
         

        if (checkreqfield.length != 0) {
            jQuery.each(checkreqfield, function (keyrequired, valuerequired) {
                jQuery("#" + valuerequired).focus();
                jQuery("#" + valuerequired).parent().append('<span class="requiredfield">Please select ' + valuerequired.split('_')[1] + '</span>');
                setTimeout(function () {
                    jQuery(".requiredfield").hide();
                }, 4000);
            });
        } else if (dupval != "" && (typeof dupval != "undefined")) {
            alert("Duplicate entry found! Please remove one entry for " + dupval);
            e.preventDefault();
        } else if (mandatory_exists == false) {
            e.preventDefault();
            alert("Please do mapping for the vryno CRM mendatory fields -- " + " " + sentence);
            jQuery("#gform-settings-edit").hide();
            jQuery("#tigformtd").show();
        } else {
            jQuery(".mappingloader").show();
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: { action: "update_mapping", formid: formid, nonce: nonce, data:arrdata },
                success: function (response) {
                    var res = response.split('0');
                    console.log(res[0]);
                    setTimeout(function () {
                        jQuery(".mappingloader").css("display", "none");
                        jQuery("#vryno_form_page").before("<p>" + res[0] + "</p>");
                    }, 3000);
                }
            });
            setTimeout(function () {
                jQuery("#adminotice").hide();
                location.reload();
            }, 6500);
        }
    });

   jQuery(document).ready(function($) {
    var newarray = {};
    var selectarray = custom_script_vars.selectvaluearry;
    jQuery.each(selectarray, function(key, valuese) {
        if (valuese !== "no data") {
            var newkey = 'input_' + key;
            newarray[newkey] = valuese;
        }
    });

    if (newarray) {
        jQuery.each(newarray, function(index, value) {
            document.getElementById(index).value = value;
        });
    }

    var Duplicates = custom_script_vars.withoutDuplicates;
    var extraFieldfound = custom_script_vars.extraFieldfound;

    if (Duplicates !== "" || Duplicates !== null) {
        // You can show a modal here if needed
    }

    if (extraFieldfound !== "" || extraFieldfound !== null) {
        // You can show a modal here if needed
    }

    // Call any function you need to run after the document is ready
    showpopup();
});


   jQuery(document).ready(function($) {
    jQuery("#modaldeletemapping").on("click", function() {
        // Creating a nonce
        var nonce = custom_script_vars.nonce;

        var formid = custom_script_vars.Form_id;
        jQuery(".mappingloader").show();
        jQuery.ajax({
            type: "POST",
            url: custom_script_vars.ajaxurl,
            data: { action: "delete_mapping", formid: formid, nonce: nonce },
            success: function(response) {
                var res = response.split('0');
                console.log(res[0]);
                setTimeout(function() {
                    jQuery(".mappingloader").css("display", "none");
                    jQuery("#vryno_form_page").before("<p>" + res[0] + "</p>");
                }, 3000);
            }
        });
        setTimeout(function() {
            jQuery("#adminotice").hide();
            location.reload();
        }, 6500);
    });
});


    jQuery("#gform-settings-edit").click(function () {
        jQuery("td#selectinput").hide();
        jQuery("td#select").show();
        jQuery("#gform-settings-edit").hide();
        jQuery("#tigform-settings").show();
        jQuery("#tigformtd").show();
     var selectarray = custom_script_vars.selectvaluearry;
        jQuery.each(selectarray, function (key, valuese) {
            //console.log( key + ": " + valuese );
            document.getElementById(key).value = valuese;
        });
    });

    jQuery("select").change(function () {
        jQuery("#tigform-settings").show();
        jQuery("#tigformtd").show();
        jQuery("#gform-settings-edit").hide();
    });

    jQuery("#deletemapping").click(function () {
        // Creating a nonce
       var nonce = custom_script_vars.nonce;
        var formid = custom_script_vars.Form_id; jQuery(".mappingloader").show();
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: { action: "delete_mapping", formid: formid, nonce: nonce },
            success: function (response) {
                var res = response.split('0');
                console.log(res[0]);
                setTimeout(function () {
                    jQuery(".mappingloader").css("display", "none");
                    jQuery("#vryno_form_page").before("<p>" + res[0] + "</p>");
                }, 3000);
            }
        });
        setTimeout(function () {
            jQuery(".gform_tab_container").load(location.href + " .gform_tab_container");
            location.reload();
        }, 6500);
    });

    function showpopup() {
        jQuery("#gf_popup_box").fadeToggle();
        jQuery("#gf_popup_box").css({ "visibility": "visible", "display": "block" });
    }

    function hidepopup() {
        jQuery("#gf_popup_box").fadeToggle();
        jQuery("#gf_popup_box").css({ "visibility": "hidden", "display": "none" });
    }

    jQuery("#cancel_button").click(function () {
        hidepopup();
    });
    jQuery("#close_button").click(function () {
        hidepopup();
    });

    // Your further JavaScript logic here
