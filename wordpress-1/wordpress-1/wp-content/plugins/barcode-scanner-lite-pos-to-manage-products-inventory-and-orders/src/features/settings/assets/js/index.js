jQuery(function () {
  setTimeout(() => {
    jQuery('a[href="#barcode-scanner-settings"]').click();
  }, 100);


  if (window.location.hash) {
    const hash = window.location.hash;
    const selector = "#bs-settings-page .nav-tab-wrapper a[href='" + hash + "']";

    if (jQuery(selector).length) {
      jQuery("#bs-settings-page .nav-tab-wrapper a").removeClass("nav-tab-active");
      jQuery(selector).addClass("nav-tab-active");

      jQuery("#bs-settings-page .tabs .settings-tab").attr("style", "display: none");
      jQuery("#bs-settings-page .tabs .settings-tab." + hash.replace("#", "") + "-tab").attr("style", "display: block");
    }
  }

  WebbsSettingsTabs();
  WebbsSettingsOptions();
  WebbsPermissionsTabs();
  WebbsAppPermissionsTabs();
  WebbsOrdersTabs();
  WebbsPricesTab();
  WebbsFieldsTab();
  WebbsProdLocations();
});


function WebbsPricesTab() {
  WebbsPricesTabEvents({
    chSelector: "form #settings_show_price_1 input[type='checkbox']",
    rowId: "tr#settings_price_1_fields",
    resultId: "price_1_field_check_result",
    checkId: "price_1_field_check"
  });
  WebbsPricesTabEvents({
    chSelector: "form #settings_show_price_2 input[type='checkbox']",
    rowId: "tr#settings_price_2_fields",
    resultId: "price_2_field_check_result",
    checkId: "price_2_field_check"
  });
  WebbsPricesTabEvents({
    chSelector: "form #settings_show_price_3 input[type='checkbox']",
    rowId: "tr#settings_price_3_fields",
    resultId: "price_3_field_check_result",
    checkId: "price_3_field_check"
  });
}

function WebbsFieldsTab() {
}

let scriptLoaded = false;

function WebbsFieldsChanges(e) {
}

function WebbsFieldsChToggle(e, className = "") {
  const fid = jQuery(e).attr("data-fid");

  if (className) {
    jQuery("div#settings_field ." + className + "[data-fid='" + fid + "']").click();
  } else {
    jQuery("div#settings_field[data-fid='" + fid + "'] .usbs_field_status").click();
  }
}

function WebbsFieldsRemove(e) {
  jQuery(".settings_field_remove").off("click").click((e) => {
    const fid = jQuery(e.target).attr("data-fid");
    const label = jQuery("#settings_field[data-fid='" + fid + "'] .usbs_field_label").val();
    const name = jQuery("#settings_field[data-fid='" + fid + "'] .usbs_field_name").val();

    if (confirm(`Are you sure delete field "${label ? label : name}"`)) {
      jQuery(e.target).closest(".settings_field_section").addClass("removed");
      jQuery(e.target).closest(".settings_field_section").find(".usbs_field_remove").val(1);
    }
  });
}

function WebbsFieldsInitAfterChanges() {
  const ordersEl = jQuery(".usbs_fields_list_sortable input.usbs_field_order, .usbs_fields_list_sortable_prices input.usbs_field_order");

  ordersEl.each(function (idx, obj) {
    const orderEl = jQuery(obj);
    orderEl.val(ordersEl.length - idx);

    const position = orderEl.closest("[data-position]").attr("data-position");
    orderEl.parent().find(".usbs_field_position").val(position);
  });

  jQuery(".settings_field_block_label").off("click").click((e) => {
    const targetEl = jQuery(e.target).hasClass("settings_field_block_label") ? jQuery(e.target) : jQuery(e.target).closest(".settings_field_block_label");
    let fid = targetEl.attr("data-fid");

    jQuery(".settings_field_body#settings_field[data-fid='" + fid + "']").toggleClass("active");
    targetEl.find(".dashicons").removeClass("active");

    if (jQuery(".settings_field_body#settings_field[data-fid='" + fid + "']").hasClass("active")) {
      targetEl.find(".dashicons-arrow-up-alt2").addClass("active");
    } else {
      targetEl.find(".dashicons-arrow-down-alt2").addClass("active");
    }
  });
}

function WebbsFieldsSortable() {
}

function WebbsPricesTabEvents({ chSelector, rowId, resultId, checkId, rout = "checkOtherPrices", isShowHide = true }) {
  const ch = jQuery(chSelector);

  if (isShowHide) ch.change(function (e) {
    jQuery(rowId).removeAttr("style");
    if (jQuery(this).prop("checked")) {
      jQuery(rowId).css("display", "auto");
    } else {
      jQuery(rowId).css("display", "none");
    }
  });

  const check = jQuery("form #" + checkId);

  check.off("click").click(function (e) {
    e.preventDefault();

    if (window['usbs_temp_timer_' + resultId]) clearTimeout(window['usbs_temp_timer_' + resultId]);

    jQuery("#" + resultId).html("");
    jQuery("#" + resultId).removeClass("active");
    check.attr("disabled", "disabled");

    let inputs = [];

    if (jQuery(rowId + " .usbs_field_type").val() == "taxonomy") inputs = jQuery(rowId + " input:not(.usbs_field_name)").serializeArray();
    else inputs = jQuery(rowId + " input:not(.usbs_taxonomy)").serializeArray();

    const data = {
      action: "barcodeScannerAction",
      rout,
      tab: check.closest("form").hasClass("bs-settings-order-fields-tab") ? "orders" : "products",
      inputs: {}
    };

    jQuery.each(inputs, function () {
      data.inputs[this.name] = this.value;
    });

    jQuery.post(
      window.usbs.ajaxUrl + "?token=" + rout,
      data,
      function (result) {
        if (result.success) {
          jQuery("#" + resultId).html(" " + result.success);
        } else if (result.error) {
          jQuery("#" + resultId).html(" " + result.error);
        }

        jQuery("#" + resultId).addClass("active");

        window['usbs_temp_timer_' + resultId] = setTimeout(() => {
          jQuery("#" + resultId).html("");
          jQuery("#" + resultId).removeClass("active");
        }, 5000);

        check.removeAttr("disabled");
      },
      "json"
    );
  });
}

function WebbsSettingsTabs() {
  jQuery("#bs-settings-page .nav-tab-wrapper a").click(function (e) {
    e.preventDefault();

    jQuery("#bs-settings-page .nav-tab-wrapper a").removeClass("nav-tab-active");
    jQuery(this).addClass("nav-tab-active");

    const tab = jQuery(this).attr("data-tab");

    jQuery("#bs-settings-page .tabs .settings-tab").attr("style", "display: none");
    jQuery("#bs-settings-page .tabs .settings-tab." + tab + "-tab").attr("style", "display: block");
  });
}

function WebbsProdLocations() {

}

function WebbsSettingsOptions() {
  jQuery("#bs_allow_frontend_integration input[type='checkbox']").change(function (e) {
    if (jQuery(this).is(":checked")) {
      jQuery("#bs_frontend_url").css("display", "table-row");
      jQuery("#bs_frontend_integration").css("display", "table-row");
      jQuery("#bs_frontend_shortcodes_integration").css("display", "table-row");

      if (jQuery("#bs_frontend_shortcodes_integration input[type='checkbox']").is(":checked")) {
        jQuery("#bs_frontend_shortcodes_docs").css("display", "table-row");
      }
    } else {
      jQuery("#bs_frontend_url").css("display", "none");
      jQuery("#bs_frontend_integration").css("display", "none");
      jQuery("#bs_frontend_shortcodes_integration").css("display", "none");
      jQuery("#bs_frontend_shortcodes_docs").css("display", "none");
    }
  });

  jQuery("#bs_frontend_shortcodes_integration input[type='checkbox']").change(function (e) {
    if (jQuery(this).is(":checked")) {
      jQuery("#bs_frontend_shortcodes_docs").css("display", "table-row");
    } else {
      jQuery("#bs_frontend_shortcodes_docs").css("display", "none");
    }
  });

  jQuery(".bs-settings-input-conditions input[data-main]").change(function (e) {
    const slug = jQuery(this).attr("data-main");

    if (!slug) return;

    if (jQuery(this).is(":checked")) {
      jQuery(".bs-settings-input-conditions [data-parent='" + slug + "']").css("display", "table-row");
    } else {
      jQuery(".bs-settings-input-conditions [data-parent='" + slug + "']").css("display", "none");
    }
  });
  jQuery(".bs-settings-input-conditions input[data-main]").change();

  jQuery("#bs_enable_locations input[type='checkbox']").change(function (e) {
    if (jQuery(this).is(":checked")) {
      jQuery("#bs-settings-locations-tab .bs_enabled_stock_locations").css("display", "table-row");
    } else {
      jQuery("#bs-settings-locations-tab .bs_enabled_stock_locations").css("display", "none");
    }
  });

  jQuery(".bs-settings-input-conditions select[data-main]").change(function (e) {
    const slug = jQuery(this).attr("data-main");

    if (!slug) return;

    if (jQuery(this).val() == "custom_field") {
      jQuery(".bs-settings-input-conditions [data-parent='" + slug + "']").css("display", "table-row");
    } else {
      jQuery(".bs-settings-input-conditions [data-parent='" + slug + "']").css("display", "none");
    }
  });
  jQuery(".bs-settings-input-conditions select[data-main]").change();
}

function WebbsPermissionsTabs() {
  jQuery(".bs-settings-chosen-select").chosen({});
}

function WebbsAppUsersActions() {
}

function WebbsAppPermissionsTabs() {
}

function WebbsOrdersTabs() {
}

function WebbsSettingsCheckboxChange(el, value) {
  const input = jQuery(el);
  const root = input.closest("tr");

  if (root && root.hasClass('read_only') && value != 1 && input) {
    const type = input.closest(".settings_field_body ").find(".usbs_field_type").val();
    const disabledFieldEl = input.closest(".settings_field_body ").find(".read_only");

    if (type == "tags" && input && disabledFieldEl) {
      input.val("1");
      disabledFieldEl.find('.usbs_field_read_only').prop('checked', true);
      return;
    }
  }

  if (input) input.val(value);
}

function Webmakeid(length) {
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;
  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
}