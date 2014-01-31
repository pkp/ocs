{**
 * registrationOptions.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registration options in scheduled conference management.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.registrationOptions"}
{assign var="pageId" value="manager.registrationOptions"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
	<li><a href="{url op="registrationTypes" clearPageContext=1}">{translate key="manager.registrationTypes"}</a></li>
	<li><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
	<li class="current"><a href="{url op="registrationOptions"}">{translate key="manager.registrationOptions"}</a></li>
</ul>

<p><span class="instruct">{translate key="manager.registrationOptions.description"}</span></p>

<div id="registrationOptions">
<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="80%">{translate key="manager.registrationOptions.name"}</td>
		<td width="20%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrationOptions item=registrationOption}
	<tr valign="top">
		<td>{$registrationOption->getRegistrationOptionName()|escape}</td>
		<td><a href="{url op="moveRegistrationOption" path=$registrationOption->getOptionId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveRegistrationOption" path=$registrationOption->getOptionId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{url op="editRegistrationOption" path=$registrationOption->getOptionId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRegistrationOption" path=$registrationOption->getOptionId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.registrationOptions.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr><td colspan="2" class="{if $registrationOptions->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $registrationOptions->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.registrationOptions.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$registrationOptions}</td>
		<td align="right">{page_links anchor="registrationOptions" name="registrationOptions" iterator=$registrationOptions}</td>
	</tr>
{/if}
</table>

<a href="{url op="createRegistrationOption"}" class="action">{translate key="manager.registrationOptions.create"}</a>
</div>
{include file="common/footer.tpl"}
