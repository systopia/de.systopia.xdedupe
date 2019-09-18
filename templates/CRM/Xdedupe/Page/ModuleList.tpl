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


<div class="xdedupe xdedupe-module-list">
    <h3>{ts domain="de.systopia.xdedupe"}Finder Modules{/ts}</h3>
    <div class="xdedupe xdedupe-module-help" id="help">
        <span>{ts domain="de.systopia.xdedupe"}Finder modules will efficiently identify <i>potential</i> duplicates and group them into tuples, i.e. pairs of two or more contacts. These can later be filtered further.{/ts}</span>
    </div>
    <table class="xdedupe-module-list">
        <thead>
            <tr>
                <td>{ts domain="de.systopia.xdedupe"}Module Name{/ts}</td>
                <td>{ts domain="de.systopia.xdedupe"}Description{/ts}</td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$finders item=finder}
                <tr>
                    <td>{$finder.name}</td>
                    <td>{$finder.help}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>

<br/>
<div class="xdedupe xdedupe-module-list">
    <h3>{ts domain="de.systopia.xdedupe"}Filter Modules{/ts}</h3>
    <div class="xdedupe xdedupe-module-help" id="help">
        <span>{ts domain="de.systopia.xdedupe"}Filter modules can restrict the tuples identified by the finder modules above. It is important, that no incorrect duplicates remain in the tuples before batch merging.{/ts}</span>
    </div>
    <table class="xdedupe-module-list">
        <thead>
        <tr>
            <td>{ts domain="de.systopia.xdedupe"}Module Name{/ts}</td>
            <td>{ts domain="de.systopia.xdedupe"}Description{/ts}</td>
        </tr>
        </thead>
        <tbody>
        {foreach from=$filters item=filter}
            <tr>
                <td>{$filter.name}</td>
                <td>{$filter.help}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>

<br/>
<div class="xdedupe xdedupe-module-list">
    <h3>{ts domain="de.systopia.xdedupe"}Picker Modules{/ts}</h3>
    <div class="xdedupe xdedupe-module-help" id="help">
        <span>{ts domain="de.systopia.xdedupe"}Picker modules have to select the main contact from a tuple, i.e. the prevailing contact that will receive all other contacts' data.{/ts}</span>
        <span>{ts domain="de.systopia.xdedupe"}The order here is vital: if the first picker cannot take a decision, it moves on to the next one in the list.{/ts}</span>
        <span>{ts domain="de.systopia.xdedupe"}If none of the selected pickers were able to select a contact, the one with the lowest ID will be used.{/ts}</span>
    </div>
    <table class="xdedupe-module-list">
        <thead>
        <tr>
            <td>{ts domain="de.systopia.xdedupe"}Module Name{/ts}</td>
            <td>{ts domain="de.systopia.xdedupe"}Description{/ts}</td>
        </tr>
        </thead>
        <tbody>
        {foreach from=$pickers item=picker}
            <tr>
                <td>{$picker.name}</td>
                <td>{$picker.help}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>

<br/>
<div class="xdedupe xdedupe-module-list">
    <h3>{ts domain="de.systopia.xdedupe"}Resolver Modules{/ts}</h3>
    <div class="xdedupe xdedupe-module-help" id="help">
        <span>{ts domain="de.systopia.xdedupe"}These modules try to automatically resolve conflicts so an automated merge can proceed.{/ts}</span>
        <span>{ts domain="de.systopia.xdedupe"}Make sure you understand what these chosen resolvers do, as they will not ask for any confirmation{/ts}</span>
    </div>
    <table class="xdedupe-module-list">
        <thead>
        <tr>
            <td>{ts domain="de.systopia.xdedupe"}Module Name{/ts}</td>
            <td>{ts domain="de.systopia.xdedupe"}Description{/ts}</td>
        </tr>
        </thead>
        <tbody>
        {foreach from=$resolvers item=resolver}
            <tr>
                <td>{$resolver.name}</td>
                <td>{$resolver.help}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>