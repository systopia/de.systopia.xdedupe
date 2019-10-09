{*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{* RUN ID *}
{$form.auto_dedupe_run.html}

{* MAIN CRITERIA *}
<div class="xdedupe-config">
  <h2>{ts domain="de.systopia.xdedupe"}Search Criteria{/ts}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Search Criteria (Finders){/ts}", {literal}{"id":"id-xdedupe-finder","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></h2>
  <div class="crm-section">
    <div class="label">{$form.finder_1.label}</div>
    <div class="content">{$form.finder_1.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.finder_2.label}</div>
    <div class="content">{$form.finder_2.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.finder_3.label}</div>
    <div class="content">{$form.finder_3.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.finder_4.label}</div>
    <div class="content">{$form.finder_4.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.finder_5.label}</div>
    <div class="content">{$form.finder_5.html}</div>
    <div class="clear"></div>
  </div>
</div>

{* FILTERS *}
<div class="xdedupe-config">
  <h2>{ts domain="de.systopia.xdedupe"}Matching Filters{/ts}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Filters{/ts}", {literal}{"id":"id-xdedupe-filter","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></h2>
  <div class="crm-section">
    <div class="label">{$form.contact_type.label}</div>
    <div class="content">{$form.contact_type.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.contact_group.label}</div>
    <div class="content">{$form.contact_group.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.contact_tag.label}</div>
    <div class="content">{$form.contact_tag.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.filters.label}</div>
    <div class="content">{$form.filters.html}</div>
    <div class="clear"></div>
  </div>
</div>

{* MERGE OPTIONS *}
<div class="xdedupe-config">
  <h2>{ts domain="de.systopia.xdedupe"}Merge Options{/ts}</h2>
  <div class="crm-section">
    <div class="label">{$form.force_merge.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Force Merge{/ts}", {literal}{"id":"id-xdedupe-forcemerge","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.force_merge.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.main_contact_1.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Main Contact{/ts}", {literal}{"id":"id-xdedupe-picker","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">
      {foreach from=$picker_fields item=picker_field}
        <div class="xdedupe-picker">{$form.$picker_field.html}</div>
      {/foreach}
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.auto_resolve.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Resolver{/ts}", {literal}{"id":"id-xdedupe-resolver","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.auto_resolve.html}</div>
    <div class="clear"></div>
  </div>
</div>

<br/>

{* BUTTONS *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{* RESULTS *}
<h1 id="xdedupe_results">{ts domain="de.systopia.xdedupe" 1=$result_count 2=$contact_count}Found %1 results with %2 contacts:{/ts}</h1>
<table class="xdedupe-result crm-ajax-table">
  <thead>
  <tr>
    <th data-data="main_contact">{ts domain="de.systopia.xdedupe"}Main Contact{/ts}</th>
    <th data-data="duplicates">{ts domain="de.systopia.xdedupe"}Duplicates{/ts}</th>
    <th data-data="links" data-orderable="false">&nbsp;</th>
  </tr>
  </thead>
</table>

{* BUTTONS *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
  /**
   * Make sure only one empty picker is showing
   */
  function xdedupe_show_pickers() {
    // first: identify the last picker that has a value
    let picker_count = cj("[name^=main_contact_]").length;
    let last_picker = 0;
    for (let i=1; i <= picker_count; i++) {
      let selector = "[name^=main_contact_" + i + "]";
      if (cj(selector).val().length > 0) {
        last_picker = i;
      }
    }

    // then: show every one before this, and hide every after
    for (let i=1; i <= picker_count; i++) {
      let selector = "[name^=main_contact_" + i + "]";
      if (i <= last_picker + 1) {
        cj(selector).parent().show();
      } else {
        cj(selector).parent().hide();
      }
    }
  }
  cj("[name^=main_contact_]")
          .change(xdedupe_show_pickers)
          .parent().hide();
  xdedupe_show_pickers();

  function xdedupe_update_table_link() {
    let picker_count = cj("[name^=main_contact_]").length;
    let pickers = [];
    for (let i=1; i <= picker_count; i++) {
      let selector = "[name^=main_contact_" + i + "]";
      if (cj(selector).val().length > 0) {
        pickers.push(cj(selector).val());
      }
    }
    CRM.$('table.xdedupe-result').data({
      "ajax": {
        "url": '{/literal}{$xdedupe_data_url}{literal}&pickers=' + pickers.join(','),
      }
    });
  }

  // trigger this function
  (function($) {
    xdedupe_update_table_link();
  })(CRM.$);
  cj("#main_contact").change(xdedupe_update_table_link);

  // 'merge' button handler
  cj("table.xdedupe-result").click(function(e) {
    if (cj(e.target).is("a.xdedupe-merge-individual")) {
      // this is the merge button click -> gather data
      // first visualise the click:
      cj(e.target).addClass("disabled")
                  .animate( { backgroundColor: "#f00" }, 500 )
                  .animate( { backgroundColor: "transparent" }, 500 )
                  .animate( { backgroundColor: "#f00" }, 500 )
                  .animate( { backgroundColor: "transparent" }, 500 )
                  .animate( { backgroundColor: "#f00" }, 500 )
                  .animate( { backgroundColor: "transparent" }, 500 )
                  .removeClass("disabled");

      let main_contact_id   = cj(e.target).parent().find("span.xdedupe-main-contact-id").text();
      let other_contact_ids = cj(e.target).parent().find("span.xdedupe-other-contact-ids").text();
      let force_merge = cj("#force_merge").prop('checked') ? "1" : "0";
      let resolvers = cj("#auto_resolve").val();
      if (resolvers == null) {
        resolvers = [];
      }
      let pickers = cj("#main_contact").val();
      if (pickers == null) {
        pickers = [];
      }
      CRM.api3("Xdedupe", "merge", {
        "main_contact_id": main_contact_id,
        "other_contact_ids": other_contact_ids,
        "force_merge": force_merge,
        "resolvers": resolvers.join(','),
        "pickers": pickers.join(','),
        "dedupe_run": "{/literal}{$dedupe_run_id}{literal}"
      }).success(function(result) {
        let ts = CRM.ts('de.systopia.xdedupe');
        if (result.tuples_merged > 0) {
          CRM.alert(ts("Tuple was merged"), ts("Success"), 'info');
          // refresh tablefailed
          // TODO: find out how to trigger ajax table reload
          if (!window.location.href.endsWith('#xdedupe_results')) {
            window.location.replace(window.location.href + '#xdedupe_results');
          }
          window.location.reload();
        } else {
          let errors = result.errors;
          errors = errors.filter(function(el, index, arr) {
            return index === arr.indexOf(el);
          });
          CRM.alert(ts("Merge failed. Remaining Conflicts: ") + errors.join(', '), ts("Merge Failed"), 'info');
        }
      }).error(function(result) {
        let ts = CRM.ts('de.systopia.xdedupe');
        CRM.alert(ts("Merge failed: " . result.error_msg), ts("Error"), 'error');
      });
      e.preventDefault();
    }
  });

</script>
{/literal}


