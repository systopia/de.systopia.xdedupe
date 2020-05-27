{*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2020 SYSTOPIA                            |
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


{crmScope extensionKey='de.systopia.xdedupe'}

    {* DELETION CONFIRMATION PAGE *}
{if $delete}
    <h3>{ts 1=$delete.name}Delete Configuration "%1"{/ts}</h3>
    <div>
        <p>
            {ts 1=$delete.name 2=$delete.id}You are about to delete configuration "%1" [%2]. You should consider simply disabling it, since all configuration data will be lost.{/ts}
        </p>
        {assign var=plugin_id value=$delete.id}
        <a class="button" href="{crmURL p="civicrm/xdedupe/manage" q="reset=1&confirmed=1&delete=$plugin_id"}">
            <span><div class="icon ui-icon-trash css_left"></div>Delete</span>
        </a>
        <a class="button" href="{crmURL p="civicrm/xdedupe/manage"}">
            <span>Back</span>
        </a>
    </div>
{else}

    {* NORMAL PAGE *}
    <div id="help">
        {ts}This is the list of you current XDedupe configurations{/ts}
        {capture assign=add_url}{crmURL p="civicrm/xdedupe/controlroom" q="reset=1"}{/capture}
        {ts 1=$add_url}To create a new configuration, simply visit the <a href="%1">CONTROL ROOM</a>, or edit one of the
        configurations below and save as new.{/ts}</a>
    </div>
    <br/>
    <table class="display" id="option11">
        <thead>
        <tr>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}ID{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Name{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Description{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Manual Execution{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Unsupervised Execution{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Scheduled Execution{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Last Run{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1">{ts}Order{/ts}</th>
            <th class="sorting_disabled" rowspan="1" colspan="1"></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$configs item=configuration}
            <tr class="{cycle values="odd-row,even-row"}">
                {assign var=config_id value=$configuration.id}
                <td>[{$configuration.id}]</td>
                <td>{$configuration.name}</td>
                <td>
                    <div title="{$configuration.description}">{$configuration.short_desc}</div>
                </td>
                <td>
                    {if $configuration.is_manual}
                        <a href="{crmURL p='civicrm/xdedupe/controlroom' q="cid=$config_id"}"
                           class="action-item crm-hover-button"
                           title="{ts}Take the configuration to the control room{/ts}">{ts}Edit &amp; Run Manually{/ts}</a>
                    {else}
                        <a class="action-item crm-hover-button disabled" title="{ts}Disabled{/ts}">{ts}Disabled{/ts}</a>
                    {/if}
                </td>
                <td>
                    {if $configuration.is_automatic}
                        <a href="{crmURL p='civicrm/xdedupe/manage' q="run=$config_id"}"
                           class="action-item crm-hover-button"
                           title="{ts}Execute fully automatic merge{/ts}">{ts}Run Unsupervised Merge{/ts}</a>
                    {else}
                        <a class="action-item crm-hover-button disabled" title="{ts}Disabled{/ts}">{ts}Disabled{/ts}</a>
                    {/if}
                </td>
                <td>
                    {if $configuration.is_scheduled}
                        <a class="action-item crm-hover-button" title="{ts}Scheduled{/ts}">{ts}Scheduled{/ts}</a>
                    {else}
                        <a class="action-item crm-hover-button disabled" title="{ts}Disabled{/ts}">{ts}Disabled{/ts}</a>
                    {/if}
                </td>
                <td>
                    <span style="white-space:nowrap;">{$configuration.last_run}</span><br/><label
                            class="xdedupe-stats xdedupe-stats-{$config_id}" title="">{ts}[Statistics]{/ts}</label>
                </td>
                <td>
                    <a class="crm-weight-arrow" href="{crmURL p='civicrm/xdedupe/manage' q="top=$config_id"}"><img
                                src="{$config->resourceBase}i/arrow/first.gif" title="Move to top" alt="Move to top"
                                class="order-icon"></a>&nbsp;
                    <a class="crm-weight-arrow" href="{crmURL p='civicrm/xdedupe/manage' q="up=$config_id"}"><img
                                src="{$config->resourceBase}i/arrow/up.gif" title="Move up one row"
                                alt="Move up one row" class="order-icon"></a>&nbsp;
                    <a class="crm-weight-arrow" href="{crmURL p='civicrm/xdedupe/manage' q="down=$config_id"}"><img
                                src="{$config->resourceBase}i/arrow/down.gif" title="Move down one row"
                                alt="Move down one row" class="order-icon"></a>&nbsp;
                    <a class="crm-weight-arrow" href="{crmURL p='civicrm/xdedupe/manage' q="bottom=$config_id"}"><img
                                src="{$config->resourceBase}i/arrow/last.gif" title="Move to bottom"
                                alt="Move to bottom" class="order-icon"></a>
                </td>
                <td>
        <span class="btn-slide crm-hover-button">{ts}Actions{/ts}
          <ul class="panel">
            <li>
              <!-- EDIT LINK -->
              <a href="{crmURL p='civicrm/xdedupe/controlroom' q="cid=$config_id"}" class="action-item crm-hover-button"
                 title="{ts}Edit (in control room){/ts}">{ts}Edit{/ts}</a>
                <!-- ENABLE/DISABLE -->
              {if $configuration.is_manual}
                  <a href="{crmURL p='civicrm/xdedupe/manage' q="disable_manual=$config_id"}"
                     class="action-item crm-hover-button small-popup"
                     title="{ts}Disable for manual execution{/ts}">{ts}Disable Manual{/ts}</a>

{else}

                  <a href="{crmURL p='civicrm/xdedupe/manage' q="enable_manual=$config_id"}"
                     class="action-item crm-hover-button small-popup"
                     title="{ts}Enable for manual execution{/ts}">{ts}Enable Manual{/ts}</a>
              {/if}
                {if $configuration.is_automatic}
                    <a href="{crmURL p='civicrm/xdedupe/manage' q="disable_automatic=$config_id"}"
                       class="action-item crm-hover-button small-popup"
                       title="{ts}Disable for automatic execution{/ts}">{ts}Disable Unsupervised{/ts}</a>

{else}

                    <a href="{crmURL p='civicrm/xdedupe/manage' q="enable_automatic=$config_id"}"
                       class="action-item crm-hover-button small-popup"
                       title="{ts}Enable for automatic execution{/ts}">{ts}Enable Unsupervised{/ts}</a>
                {/if}
                {if $configuration.is_scheduled}
                    <a href="{crmURL p='civicrm/xdedupe/manage' q="disable_scheduled=$config_id"}"
                       class="action-item crm-hover-button small-popup"
                       title="{ts}Don't schedule for automatic execution{/ts}">{ts}Don't Schedule{/ts}</a>

{else}

                    <a href="{crmURL p='civicrm/xdedupe/manage' q="enable_scheduled=$config_id"}"
                       class="action-item crm-hover-button small-popup"
                       title="{ts}Schedule for automatic execution{/ts}">{ts}Schedule{/ts}</a>
                {/if}

              <!-- OTHER LIFECYCLE -->
              <a href="{crmURL p='civicrm/xdedupe/manage' q="delete=$config_id"}"
                 class="action-item crm-hover-button small-popup"
                 title="{ts}Delete Configuration{/ts}">{ts}Delete{/ts}</a>
            </li>
          </ul>
        </span>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <br/>
    <div id="help">
        {ts}
            <strong>Caution!</strong>
            Unsupervised execution of these Xdedupe configurations will perform automatic merges. If these merges turn out to be wrong, it's
            <strong>a lot of work</strong>
            to roll them back. Be sure you know what you're doing and test well.{/ts}
    </div>
{/if}
    <script type="text/javascript">
        // reset the URL
        window.history.replaceState("", "", "{$baseurl}");

        {literal}
        // render the stats as tooltips
        cj(document).ready(function () {
            let stats = CRM.vars.xdedeupe.stats;
            for (let config_id in stats) {
                cj('.xdedupe-stats-' + config_id).tooltip({content: stats[config_id]});
            }
        });
        {/literal}
    </script>
{/crmScope}