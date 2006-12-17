{**
 * cfp.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Event call-for-papers page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="event.cfp"}
{include file="common/header.tpl"}

{if $showCFP or $showCFPExpired}
	<br />
	<center><h3>{translate key="event.cfp"}</h3></center>
	{url|assign:cfpUrl page="author" op="submit" loginMessage="author.submit.authorSubmitLoginMessage"}

	<table width="100%" class="listing">
		<tr>
			<td class="headseparator">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td>
				{translate key="event.cfpMessage" submissionOpenDate=$submissionOpenDate submissionCloseDate=$submissionCloseDate}
					{if $showCFP}
						<br />
						<a href={$cfpUrl}>{translate key="author.submit.startHere"}</a>
					{/if}
			</td>
		</tr>
		{if $showCFPExpired}
			<tr valign="top">
				<td>
					{translate key="event.cfpExpiredMessage"}
				</td>
			</tr>
		{/if}
		<tr>
			<td class="endseparator">&nbsp;</td>
		</tr>
		</table>
{/if}

<div>{$cfp|nl2br}</div>

{include file="common/footer.tpl"}
