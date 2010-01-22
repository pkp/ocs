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
{* WARNING: This page should be kept roughly synchronized with the
   implementation of reader statistics in the About page.          *}
<a name="statistics"></a>
<h3>{translate key="manager.statistics.statistics"}</h3>
<p>{translate key="manager.statistics.statistics.description"}</p>

<p>{translate key="manager.statistics.statistics.selectTracks"}</p>
<form action="{url op="saveStatisticsTracks"}" method="post">
	<select name="trackIds[]" class="selectMenu" multiple="multiple" size="5">
		{foreach from=$tracks item=track}
			<option {if in_array($track->getTrackId(), $trackIds)}selected {/if}value="{$track->getTrackId()}">{$track->getTrackTitle()}</option>
		{/foreach}
	</select><br/>&nbsp;<br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br/>

<form action="{url op="savePublicStatisticsList"}" method="post">
<table width="100%" class="data">
	<tr valign="top">
		<td width="25%" class="label"><h4>{translate key="common.year"}</h4></td>
		<td width="75%" colspan="2" class="value">
			<h4><a class="action" href="{url statisticsYear=$statisticsYear-1}">{translate key="navigation.previousPage"}</a>&nbsp;{$statisticsYear|escape}&nbsp;<a class="action" href="{url statisticsYear=$statisticsYear+1}">{translate key="navigation.nextPage"}</a></h4>
		</td>
	</tr>

	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statItemsPublished" name="statItemsPublished" {if $statItemsPublished}checked {/if}/><label for="statItemsPublished">{translate key="manager.statistics.statistics.itemsPublished"}</label></td>
		<td width="80%" colspan="2" class="value">{$paperStatistics.numPublishedSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statNumSubmissions" name="statNumSubmissions" {if $statNumSubmissions}checked {/if}/><label for="statNumSubmissions">{translate key="manager.statistics.statistics.numSubmissions"}</label></td>
		<td width="80%" colspan="2" class="value">{$paperStatistics.numSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statPeerReviewed" name="statPeerReviewed" {if $statPeerReviewed}checked {/if}/><label for="statPeerReviewed">{translate key="manager.statistics.statistics.peerReviewed"}</label></td>
		<td width="80%" colspan="2" class="value">{$limitedPaperStatistics.numReviewedSubmissions}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statCountAccept" name="statCountAccept" {if $statCountAccept}checked {/if}/>&nbsp;&nbsp;<label for="statCountAccept">{translate key="manager.statistics.statistics.count.accept"}</label></td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsAccept percentage=$limitedPaperStatistics.submissionsAcceptPercent}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statCountDecline" name="statCountDecline" {if $statCountDecline}checked {/if}/>&nbsp;&nbsp;<label for="statCountDecline">{translate key="manager.statistics.statistics.count.decline"}</label></td>
		<td width="80%" colspan="2" class="value">{translate key="manager.statistics.statistics.count.value" count=$limitedPaperStatistics.submissionsDecline percentage=$limitedPaperStatistics.submissionsDeclinePercent}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statDaysPerReview" name="statDaysPerReview" {if $statDaysPerReview}checked {/if}/>&nbsp;&nbsp;<label for="statDaysPerReview">{translate key="manager.statistics.statistics.daysPerReview"}</label></td>
		<td colspan="2" class="value">
			{assign var=daysPerReview value=$reviewerStatistics.daysPerReview}
			{math equation="round($daysPerReview)"}
		</td>
	</tr>
	{*<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statDaysToPublication" name="statDaysToPublication" {if $statDaysToPublication}checked {/if}/>&nbsp;&nbsp;<label for="statDaysToPublication">{translate key="manager.statistics.statistics.daysToPublication"}</label></td>
		<td colspan="2" class="value">{$limitedPaperStatistics.daysToPublication}</td>
	</tr>*}
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statRegisteredUsers" name="statRegisteredUsers" {if $statRegisteredUsers}checked {/if}/><label for="statRegisteredUsers">{translate key="manager.statistics.statistics.users"}</label></td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.totalUsersCount numNew=$userStatistics.totalUsersCount}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="checkbox" id="statRegisteredReaders" name="statRegisteredReaders" {if $statRegisteredReaders}checked {/if}/><label for="statRegisteredReaders">{translate key="manager.statistics.statistics.readers"}</label></td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$allUserStatistics.reader|default:"0" numNew=$userStatistics.reader|default:"0"}</td>
	</tr>

	<tr valign="top">
		<td colspan="3" class="label"><input type="checkbox" id="statRegistrations" name="statRegistrations" {if $statRegistrations}checked {/if}/><label for="statRegistrations">{translate key="manager.statistics.statistics.registrations"}</label></td>
	</tr>
	{foreach from=$allRegistrationStatistics key=type_id item=stats}
	<tr valign="top">
		<td width="20%" class="label">&nbsp;&nbsp;{$stats.name}:</td>
		<td colspan="2" class="value">{translate key="manager.statistics.statistics.totalNewValue" numTotal=$stats.count|default:"0" numNew=$registrationStatistics.$type_id.count|default:"0"}</td>
	</tr>
	{/foreach}
</table>
<p>{translate key="manager.statistics.statistics.note"}</p>

{translate key="manager.statistics.statistics.makePublic"}<br/>
<input type="submit" class="button defaultButton" value="{translate key="common.record"}"/>
</form>
