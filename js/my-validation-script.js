/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *
 *
 * Use custom validation for fields here
 * Also can place other javascript and jquery stuff here
 */

//Below function is used to post the values of vryno addon setting to a function where we are getting access token from vryno using an api
function connectwithvryno() {
  var nonce = ajax_object.nonce;
  var vryno_server_url = jQuery("#vryno_server_url").val();
  var refresh_token = jQuery("#refresh_token").val();
  var client_id = jQuery("#client_id").val();
  var client_secret = jQuery("#client_secret").val();
  var grant_type = jQuery("#grant_type").val();
  var submit_vryno_details = jQuery("#submit_vryno_details").val();
  var instance_id = jQuery("#instance_id").val();
  if (
    refresh_token == "" ||
    client_id == "" ||
    client_secret == "" ||
    grant_type == "" ||
    vryno_server_url == "" ||
    vryno_server_url == "/"
  ) {
    //e.preventDefault();
    jQuery("#haedingmain").before(
      "<div class='error notice' id='adminotice'><strong>Please map the field first!</strong></div>"
    );
    setTimeout(function () {
      jQuery("#adminotice").hide();
    }, 3000);
  } else {
    jQuery(".loader").show();
    jQuery.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        action: "vryno_connect",
        vryno_server_url: vryno_server_url,
        refresh_token: refresh_token,
        client_id: client_id,
        client_secret: client_secret,
        grant_type: grant_type,
        submit_vryno_details: "",
        instance_id: instance_id,
         nonce: nonce,
      },
      success: function (response) {
        var res = response.split("0");
        setTimeout(function () {
          jQuery(".loader").css("display", "none");
        }, 3000);
        jQuery("#haedingmain").before("<p>" + res[0] + "</p>");
      },
      error: function (jqXHR, exception) {
        if (jqXHR.status === 0) {
          alert("Not connect.\n Verify Network.");
        } else if (jqXHR.status == 404) {
          alert("Requested page not found. [404]");
        } else if (jqXHR.status == 500) {
          alert("Internal Server Error [500] " + jqXHR.status);
        } else if (exception === "parsererror") {
          alert("Requested JSON parse failed.");
        } else if (exception === "timeout") {
          alert("Time out error.");
        } else if (exception === "abort") {
          alert("Ajax request aborted.");
        } else {
          alert("Uncaught Error.\n" + jqXHR.responseText);
        }
      },
    });
    setTimeout(function () {
      jQuery(".wrap").load(location.href + " #haedingmain");
      jQuery("#adminotice").hide();
    }, 7000);
  }
}

//recordupdate function is called when updating fields of vrynoconnect template
function recordupdate() {
  jQuery("#refresh_token").removeAttr("disabled");
  jQuery("#client_id").removeAttr("disabled");
  jQuery("#client_secret").removeAttr("disabled");
  jQuery("#vryno_server_url").removeAttr("disabled");
  jQuery("#email").removeAttr("disabled");
  jQuery("#password").removeAttr("disabled");
  jQuery("#instance_id").removeAttr("disabled");
  jQuery("#refresh_token").attr("placeholder", "");
  jQuery("#client_id").attr("placeholder", "");
  jQuery("#client_secret").attr("placeholder", "");
  jQuery("#vryno_server_url").attr("placeholder", "");
  jQuery("#email").attr("placeholder", "");
  jQuery("#password").attr("placeholder", "");
  jQuery("#submit_vryno_details").show();
  jQuery("#submit_vryno_update_details").hide();
}

//updating vryno fields in database using vryno api
function updatenewfields() {
  var nonce = ajax_object.nonce;
  var vryno_server_url = jQuery("#vryno_server_url").attr("placeholder");
  var refresh_token = jQuery("#refresh_token").attr("placeholder");
  var client_id = jQuery("#client_id").attr("placeholder");
  var client_secret = jQuery("#client_secret").attr("placeholder");
  var grant_type = jQuery("#grant_type").attr("placeholder");
  var refreshvrynofield = jQuery("#refreshvrynofield").val();
  jQuery(".loader").show();
  jQuery.ajax({
    type: "POST",
    url: ajaxurl,
    data: {
      action: "refresh_fields",
      vryno_server_url: vryno_server_url,
      refresh_token: refresh_token,
      client_id: client_id,
      client_secret: client_secret,
      grant_type: grant_type,
      refreshvrynofield: refreshvrynofield,
       nonce: nonce,
    },
    success: function (response) {
      var res = response.split("0");
      console.log(res[0]);
      setTimeout(function () {
        jQuery(".loader").css("display", "none");
      }, 3000);
      jQuery("#haedingmain").before("<p>" + res[0] + "</p>");
    },
  });
  setTimeout(function () {
    jQuery(".wrap").load(location.href + " #haedingmain");
    jQuery("#adminotice").hide();
  }, 7000);
}


