{**
 * statistics.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.statistics"}
{include file="common/header.tpl"}
{/strip}

{* WARNING: This page should be kept roughly synchronized with the
   implementation of the Conference Manager's statistics page.        *}
<div id="statistics">
<table width="100%" class="data">
	{if $statNumPublishedIssues}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.numIssues"}</td>
		<td width="70%" class="value">{$issueStatistics.numPublishedIssues}</td>
	</tr>{/if}

	{if $statItemsPublished}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.itemsPublished"}</td>
		<td width="70%" class="value">{$paperStatistics.numPublishedSubmissions}</td>
	</tr>{/if}
	{if $statNumSubmissions}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.numSubmissions"}</td>
		<td width="70%" class="value">{$paperStatistics.numSubmissions}</td>
	</tr>{/if}
	{if $statPeerReviewed}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.peerReviewed"}</td>
		<td width="70%" class="value">{$limitedPaperStatistics.numReviewedSubmissions}</td>
	</tr>{/if}
	{if $statCountAccept}<tr valign="top">
		<td width="30%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.accept"}</td>
		<td width="70%" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsAccept percentage=$limitedPaperStatistics.submissionsAcceptPercent}</td>
	</tr>{/if}
	{if $statCountDecline}<tr valign="top">
		<td width="30%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.decline"}</td>
		<td width="70%" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsDecline percentage=$limitedPaperStatistics.submissionsDeclinePercent}</td>
	</tr>{/if}
	{if $statDaysPerReview}<tr valign="top">
		<td width="30%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.daysPerReview"}</td>
		<td width="70%" class="value">
			{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
			{math equation="round($daysPerReview)"}
		</td>
	</tr>{/if}
	{if $statRegisteredUsers}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.users"}</td>
		<td width="70%" class="value">{$userStatistics.totalUsersCount}</td>
	</tr>{/if}
	{if $statRegisteredReaders}<tr valign="top">
		<td width="30%" class="label">{translate key="manager.statistics.statistics.readers"}</td>
		<td width="70%" class="value">{$userStatistics.reader|default:"0"}</td>
	</tr>{/if}

	{if $statRegistrations}
		<tr valign="top">
			<td colspan="2" class="label">{translate key="manager.statistics.statistics.registrations"}</td>
		</tr>
		{foreach from=$registrationStatistics item=stats}
		<tr valign="top">
			<td width="20%" class="label">&nbsp;&nbsp;{$stats.name}:</td>
			<td width="70%" class="value">{$stats.count|default:"0"}</td>
		</tr>
		{/foreach}
	{/if}
</table>
</div>
{include file="common/footer.tpl"}
