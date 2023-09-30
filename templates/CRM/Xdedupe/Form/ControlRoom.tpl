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

{* CONFIGURATION *}
<div class="crm-accordion-wrapper crm-xdedupe-config {if not $config_showing}collapsed{/if}">
    <div class="crm-accordion-header active">{ts domain="de.systopia.xdedupe"}{$config_header}{/ts}</div>
    <div class="crm-accordion-body">
        <div class="crm-section">
            <div class="label">{$form.name.label}</div>
            <div class="content">{$form.name.html}</div>
            <div class="clear"></div>
        </div>
        <div class="crm-section">
            <div class="label">{$form.description.label}</div>
            <div class="content">{$form.description.html}</div>
            <div class="clear"></div>
        </div>
    </div>
</div>

{* MAIN CRITERIA *}
<div class="xdedupe-config">
    <h2>{ts domain="de.systopia.xdedupe"}Search Criteria{/ts}&nbsp;<a
                onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Search Criteria (Finders){/ts}", {literal}{"id":"id-xdedupe-finder","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></h2>
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
    <h2>{ts domain="de.systopia.xdedupe"}Matching Filters{/ts}&nbsp;<a
                onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Filters{/ts}", {literal}{"id":"id-xdedupe-filter","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></h2>
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
        <div class="label">{$form.contact_group_exclude.label}</div>
        <div class="content">{$form.contact_group_exclude.html}</div>
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
        <div class="label">{$form.force_merge.label}&nbsp;<a
                    onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Force Merge{/ts}", {literal}{"id":"id-xdedupe-forcemerge","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                    href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
        <div class="content">{$form.force_merge.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.main_contact_1.label}&nbsp;<a
                    onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Main Contact{/ts}", {literal}{"id":"id-xdedupe-picker","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                    href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
        <div class="content">
            {foreach from=$picker_fields item=picker_field}
                <div class="xdedupe-picker">{$form.$picker_field.html}</div>
            {/foreach}
        </div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.auto_resolve.label}&nbsp;<a
                    onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Resolver{/ts}", {literal}{"id":"id-xdedupe-resolver","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                    href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
        <div class="content">{$form.auto_resolve.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.merge_log.label}&nbsp;<a
                    onclick='CRM.help("{ts domain="de.systopia.xdedupe"}Merge Log{/ts}", {literal}{"id":"id-merge-log","file":"CRM\/Xdedupe\/Form\/ControlRoom"}{/literal}); return false;'
                    href="#" title="{ts domain="de.systopia.xdedupe"}Help{/ts}" class="helpicon">&nbsp;</a></div>
        <div class="content">{$form.merge_log.html}</div>
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


