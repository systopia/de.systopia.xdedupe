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
  <h2>{ts domain="de.systopia.xdedupe"}Search Criteria{/ts}</h2>
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
  <h2>{ts domain="de.systopia.xdedupe"}Matching Filters{/ts}</h2>
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
    <div class="label">{$form.force_merge.label}</div>
    <div class="content">{$form.force_merge.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.main_contact.label}</div>
    <div class="content">{$form.main_contact.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.auto_resolve.label}</div>
    <div class="content">{$form.auto_resolve.html}</div>
    <div class="clear"></div>
  </div>
</div>

{* BUTTONS *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{* RESULTS *}
<br/>
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

{literal}
<script type="text/javascript">
  (function($) {
    CRM.$('table.xdedupe-result').data({
      "ajax": {
        "url": {/literal}'{$xdedupe_data_url}'{literal},
      }
    });
  })(CRM.$);

  // 'merge' button handler
  cj("table.xdedupe-result").click(function(e) {
    if (cj(e.target).is("a.xdedupe-merge-individual")) {
      // this is the merge button click -> gather data
      let main_contact_id   = cj(e.target).parent().find("span.xdedupe-main-contact-id").text();
      let other_contact_ids = cj(e.target).parent().find("span.xdedupe-other-contact-ids").text();
      let force_merge = cj("#force_merge").prop('checked') ? "1" : "0";
      let resolvers = cj("#auto_resolve").val();
      if (resolvers == null) {
        resolvers = [];
      }
      CRM.api3("Xdedupe", "merge", {
        "main_contact_id": main_contact_id,
        "other_contact_ids": other_contact_ids,
        "force_merge": force_merge,
        "resolvers": resolvers.join(','),
        "dedupe_run": "{/literal}{$dedupe_run_id}{literal}"
      }).success(function(result) {
        let ts = CRM.ts('de.systopia.xdedupe');
        if (result.tuples_merged > 0) {
          CRM.alert(ts("Tuple was merged"), ts("Success"), 'info');
          // refresh table
          // TODO: find out how to trigger ajax table reload
          if (!window.location.href.endsWith('#xdedupe_results')) {
            window.location.replace(window.location.href + '#xdedupe_results');
          }
          window.location.reload();
        } else {
          CRM.alert(ts("Merge failed: ") + result.errors, ts("Failed"), 'info');
        }
      }).error(function(result) {
        let ts = CRM.ts('de.systopia.xdedupe');
        CRM.alert(ts("XDedupe Merge failed: " . result.error_msg), ts("Error"), 'error');
      });
      console.log(e.target);
      e.preventDefault();
    }
  });

</script>
{/literal}


