{**
 * statistics.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 * $Id$
 *}
{assign var="pageTitle" value="about.statistics"}
{include file="common/header.tpl"}

{* WARNING: This page should be kept roughly synchronized with the
   implementation of the Conference Manager's statistics page.        *}
<a name="statistics"></a>

<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label"><h4>{translate key="common.year"}</h4></td>
		<td width="75%" colspan="2" class="value">
			<h4><a class="action" href="{url statisticsYear=$statisticsYear-1}">{translate key="navigation.previousPage"}</a>&nbsp;{$statisticsYear|escape}&nbsp;<a class="action" href="{url statisticsYear=$statisticsYear+1}">{translate key="navigation.nextPage"}</a></h4>
		</td>
	</tr>

	{if $statNumPublishedIssues}<tr valign="top">
		<td class="label">{translate key="manager.statistics.statistics.numIssues"}</td>
		<td colspan="2" class="value">{$issueStatistics.numPublishedIssues}</td>
	</tr>{/if}

	{if $statItemsPublished}<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.itemsPublished"}</td>
		<td width="80%" colspan="2" class="value">{$paperStatistics.numPublishedSubmissions}</td>
	</tr>{/if}
	{if $statNumSubmissions}<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.numSubmissions"}</td>
		<td width="80%" colspan="2" class="value">{$paperStatistics.numSubmissions}</td>
	</tr>{/if}
	{if $statPeerReviewed}<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.peerReviewed"}</td>
		<td width="80%" colspan="2" class="value">{$limitedPaperStatistics.numReviewedSubmissions}</td>
	</tr>{/if}
	{if $statCountAccept}<tr valign="top">
		<td width="20%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.accept"}</td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsAccept percentage=$limitedPaperStatistics.submissionsAcceptPercent}</td>
	</tr>{/if}
	{if $statCountDecline}<tr valign="top">
		<td width="20%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.count.decline"}</td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsDecline percentage=$limitedPaperStatistics.submissionsDeclinePercent}</td>
	</tr>{/if}
	{if $statDaysPerReview}<tr valign="top">
		<td width="20%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.daysPerReview"}</td>
		<td colspan="2" class="value">
			{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
			{math equation="round($daysPerReview)"}
		</td>
	</tr>{/if}
	{if $statDaysToPublication}<tr valign="top">
		<td width="20%" class="label">&nbsp;&nbsp;{translate key="manager.statistics.statistics.daysToPublication"}</td>
		<td colspan="2" class="value">{$limitedPaperStatistics.daysToPublication}</td>
	</tr>{/if}
	{if $statRegisteredUsers}<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.users"}</td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.totalUsersCount numNew=$userStatistics.totalUsersCount}</td>
	</tr>{/if}
	{if $statRegisteredReaders}<tr valign="top">
		<td width="20%" class="label">{translate key="manager.statistics.statistics.readers"}</td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.reader|default:"0" numNew=$userStatistics.reader|default:"0"}</td>
	</tr>{/if}

	{if $statRegistrations}
		<tr valign="top">
			<td colspan="3" class="label">{translate key="manager.statistics.statistics.registrations"}</td>
		</tr>
		{foreach from=$allRegistrationStatistics key=type_id item=stats}
		<tr valign="top">
			<td width="20%" class="label">&nbsp;&nbsp;{$stats.name}:</td>
			<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$stats.count|default:"0" numNew=$registrationStatistics.$type_id.count|default:"0"}</td>
		</tr>
		{/foreach}
	{/if}
</table>

{include file="common/footer.tpl"}
