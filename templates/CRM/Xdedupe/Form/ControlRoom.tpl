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

{* RESULTS *}
<br/>
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
</script>
{/literal}


{* RESULTS }
<div class="xdedupe-config xdedupe-result" id="xdedupe-result">
  {if $tuples}
    <h2>{ts domain="de.systopia.xdedupe" 1=$result_count 2=$contact_count}%1 results with %2 contacts:{/ts}</h2>
    <table id="xdedupe_preview_table" class="xdedupe-preview">
      <thead>
        <tr>
          <th>{ts domain="de.systopia.xdedupe"}Main Contact{/ts}</th>
          <th>{ts domain="de.systopia.xdedupe"}Duplicates{/ts}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      {foreach from=$tuples item=tuple}
        <tr>
          <td>{$tuple.main.image} <a target="_blank" href="{$tuple.main.link}>{$tuple.main.display_name}</a></td>
          <td>
            {foreach from=$tuple.other item=other}
              {$other.image} <a target="_blank" href="{$other.link}">{$other.display_name}</a><br/>
            {/foreach}
          </td>
          <td>
            <span>
              <a href="#xdedupe-result" class="action-item crm-hover-button no-popup" title="{ts domain="de.systopia.xdedupe"}View Comparison{/ts}">{ts domain="de.systopia.xdedupe"}Compare{/ts}</a>
              <a href="#xdedupe-result" class="action-item crm-hover-button no-popup" title="{ts domain="de.systopia.xdedupe"}Merge All{/ts}">{ts domain="de.systopia.xdedupe"}Merge{/ts}</a>
              {if $tuple.main.mergelink}
              <a href="{$tuple.main.mergelink}" class="action-item crm-hover-button no-popup" title="{ts domain="de.systopia.xdedupe"}CiviCRM's Manual Merge{/ts}">{ts domain="de.systopia.xdedupe"}Manual{/ts}</a>
              {/if}
            </span>
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {else}
    <h2>{ts domain="de.systopia.xdedupe"}No duplicates found!{/ts}</h2>
  {/if}
</div>
*}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

