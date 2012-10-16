{**
 * templates/manager/statistics/statistics.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the statistics table.
 *
 *}
{* WARNING: This page should be kept roughly synchronized with the
   implementation of reader statistics in the About page.          *}
<div id="statistics">
<h3>{translate key="manager.statistics.statistics"}</h3>
<p>{translate key="manager.statistics.statistics.description"}</p>

<p>{translate key="manager.statistics.statistics.selectTracks"}</p>
<form class="pkp_form" action="{url op="saveStatisticsTracks"}" method="post">
	<select name="trackIds[]" class="selectMenu" multiple="multiple" size="5">
		{foreach from=$tracks item=track}
			<option {if in_array($track->getId(), $trackIds)}selected {/if}value="{$track->getId()}">{$track->getLocalizedTitle()}</option>
		{/foreach}
	</select><br/>&nbsp;<br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br/>

<form class="pkp_form" action="{url op="savePublicStatisticsList"}" method="post">
<table width="100%" class="data">
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statItemsPublished" name="statItemsPublished" {if $statItemsPublished}checked {/if}/><label for="statItemsPublished">{translate key="manager.statistics.statistics.itemsPublished"}</label></td>
		<td width="70%" class="value">{$paperStatistics.numPublishedSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statNumSubmissions" name="statNumSubmissions" {if $statNumSubmissions}checked {/if}/><label for="statNumSubmissions">{translate key="manager.statistics.statistics.numSubmissions"}</label></td>
		<td width="70%" class="value">{$paperStatistics.numSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statPeerReviewed" name="statPeerReviewed" {if $statPeerReviewed}checked {/if}/><label for="statPeerReviewed">{translate key="manager.statistics.statistics.peerReviewed"}</label></td>
		<td width="70%" class="value">{$limitedPaperStatistics.numReviewedSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statCountAccept" name="statCountAccept" {if $statCountAccept}checked {/if}/>&nbsp;&nbsp;<label for="statCountAccept">{translate key="manager.statistics.statistics.count.accept"}</label></td>
		<td width="70%" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsAccept percentage=$limitedPaperStatistics.submissionsAcceptPercent}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statCountDecline" name="statCountDecline" {if $statCountDecline}checked {/if}/>&nbsp;&nbsp;<label for="statCountDecline">{translate key="manager.statistics.statistics.count.decline"}</label></td>
		<td width="70%" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsDecline percentage=$limitedPaperStatistics.submissionsDeclinePercent}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statDaysPerReview" name="statDaysPerReview" {if $statDaysPerReview}checked {/if}/>&nbsp;&nbsp;<label for="statDaysPerReview">{translate key="manager.statistics.statistics.daysPerReview"}</label></td>
		<td width="70%" class="value">
			{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
			{math equation="round($daysPerReview)"}
		</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statRegisteredUsers" name="statRegisteredUsers" {if $statRegisteredUsers}checked {/if}/><label for="statRegisteredUsers">{translate key="manager.statistics.statistics.users"}</label></td>
		<td width="70%" class="value">{$userStatistics.totalUsersCount}</td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label"><input type="checkbox" id="statRegisteredReaders" name="statRegisteredReaders" {if $statRegisteredReaders}checked {/if}/><label for="statRegisteredReaders">{translate key="manager.statistics.statistics.readers"}</label></td>
		<td width="70%" class="value">{$userStatistics.reader|default:"0"}</td>
	</tr>

	<tr valign="top">
		<td colspan="2" class="label"><input type="checkbox" id="statRegistrations" name="statRegistrations" {if $statRegistrations}checked {/if}/><label for="statRegistrations">{translate key="manager.statistics.statistics.registrations"}</label></td>
	</tr>
	{foreach from=$registrationStatistics item=stats}
	<tr valign="top">
		<td width="30%" class="label">&nbsp;&nbsp;{$stats.name}:</td>
		<td width="70%" class="value">{$stats.count|default:"0"}</td>
	</tr>
	{/foreach}
</table>
<p>{translate key="manager.statistics.statistics.note"}</p>

{translate key="manager.statistics.statistics.makePublic"}<br/>
<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>
</form>
</div>

