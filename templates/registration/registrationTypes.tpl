{**
 * registrationTypes.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registration types in scheduled conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.registrationTypes"}
{assign var="pageId" value="manager.registrationTypes"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
	<li class="current"><a href="{url op="registrationTypes" clearPageContext=1}">{translate key="manager.registrationTypes"}</a></li>
	<li><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
	<li><a href="{url op="registrationOptions"}">{translate key="manager.registrationOptions"}</a></li>
</ul>

<br />

<div id="registrationTypes">
<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="58%">{translate key="manager.registrationTypes.name"}</td>
		<td width="30%">{translate key="manager.registrationTypes.cost"}</td>
		<td width="12%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrationTypes item=registrationType}
	<tr valign="top">
		<td>{$registrationType->getRegistrationTypeName()|escape}</td>
		<td>{$registrationType->getCost()|string_format:"%.2f"}&nbsp;({$registrationType->getCurrencyStringShort()})</td>
		<td><a href="{url op="moveRegistrationType" path=$registrationType->getTypeId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveRegistrationType" path=$registrationType->getTypeId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{url op="editRegistrationType" path=$registrationType->getTypeId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRegistrationType" path=$registrationType->getTypeId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.registrationTypes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr><td colspan="3" class="{if $registrationTypes->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $registrationTypes->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.registrationTypes.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$registrationTypes}</td>
		<td colspan="2" align="right">{page_links anchor="registrationTypes" name="registrationTypes" iterator=$registrationTypes}</td>
	</tr>
{/if}
</table>

<a href="{url op="createRegistrationType"}" class="action">{translate key="manager.registrationTypes.create"}</a>
</div>
{include file="common/footer.tpl"}
