{**
 * sidebar.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu.
 *
 * $Id$
 *}
<div id="sidebar">
	<div class="block">
		<a href="http://pkp.sfu.ca/ocs/" id="developedBy">{translate key="common.openConferenceSystems"}</a>
	</div>
	
	<!-- <div class="block">
		<a href="javascript:openHelp('{if $helpTopicId}{get_help_id key="$helpTopicId" url="true"}{else}{url page="help"}{/if}')">{translate key="navigation.conferenceHelp"}</a>
	</div> -->
		
	<div class="block">
		<span class="blockTitle">{translate key="navigation.user"}</span>
		{if $isUserLoggedIn}
		{translate key="navigation.loggedInAs"}<br />
		<strong>{$loggedInUsername|escape}</strong>
		
		<ul>
			{if $hasOtherConferences}
			<li><a href="{url conference="index" page="user"}">{translate key="navigation.myConferences"}</a></li>
			{/if}
			<li><a href="{url page="user" op="profile"}">{translate key="navigation.myProfile"}</a></li>
			<li><a href="{url page="login" op="signOut"}">{translate key="navigation.logout"}</a></li>
		{if $userSession->getSessionVar('signedInAs')}
			<li><a href="{url page="manager" op="signOutAsUser"}">{translate key="manager.people.signOutAsUser"}</a></li>
		{/if}
		</ul>
		{else}
		<form method="post" action="{url page="login" op="signIn"}">
		<table>
		<tr>
			<td><label for="sidebar-username">{translate key="user.username"}</label></td>
			<td><input type="text" id="sidebar-username" name="username" value="" size="12" maxlength="32" class="textField" /></td>
		</tr>
		<tr>
			<td><label for="sidebar-password">{translate key="user.password"}</label></td>
			<td><input type="password" id="sidebar-password" name="password" value="{$password|escape}" size="12" maxlength="32" class="textField" /></td>
		</tr>
		<tr>
			<td colspan="2"><input type="checkbox" id="remember" name="remember" value="1" /> <label for="remember">{translate key="user.login.rememberMe"}</label></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" value="{translate key="user.login"}" class="button" /></td>
		</tr>
		</table>
		</form>
		{/if}
	</div>
	
	{if $sidebarTemplate}
		{include file=$sidebarTemplate}
	{/if}
	
	{if $enableLanguageToggle}
	<div class="block">
		<span class="blockTitle">{translate key="common.language"}</span>
		<form action="#">
			<select size="1" name="locale" onchange="location.href={if $languageToggleNoUser}'{$currentUrl}{if strstr($currentUrl, '?')}&{else}?{/if}setLocale='+this.options[this.selectedIndex].value{else}('{url page="user" op="setLocale" path="NEW_LOCALE" source=$smarty.server.REQUEST_URI escape=false}'.replace('NEW_LOCALE', this.options[this.selectedIndex].value)){/if}" class="selectMenu">{html_options options=$languageToggleLocales selected=$currentLocale}</select>
		</form>
	</div>
	{/if}
		
	<div class="block">
		<span class="blockTitle">{translate key="navigation.conferenceContent"}</span>
		
		<span class="blockSubtitle">{translate key="navigation.search"}</span>
		<form method="post" action="{url page="search" op="results"}">
		<table>
		<tr>
			<td><input type="text" id="query" name="query" size="15" maxlength="255" value="" class="textField" /></td>
		</tr>
		<tr>
			<td><select name="searchField" size="1" class="selectMenu">
				{html_options_translate options=$paperSearchByOptions}
			</select></td>
		</tr>
		<tr>
			<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
		</tr>
		</table>
		</form>
		
		<br />
	
	{if $currentSchedConf}
		<span class="blockSubtitle">{translate key="schedConf.contents"}</span>

		<ul class="plain">
			<li>&#187; <a href="{url page="schedConf" op="overview"}">{translate key="schedConf.overview"}</a></li>
			{if $schedConfShowCFP}
				<li>&#187; <a href="{url page="schedConf" op="cfp"}">{translate key="schedConf.cfp"}</a>{if $submissionOpenDate} ({$submissionOpenDate|date_format:$dateFormatLong} - {$submissionCloseDate|date_format:$dateFormatLong}){/if}</li>
			{/if}
			{if $schedConfShowSubmissionLink}
				<li>&#187; <a href="{url page="presenter" op="submit" requiresPresenter="1"}">{translate key="schedConf.proposalSubmission"}</a></li>
			{/if}
			<li>&#187; <a href="{url page="schedConf" op="trackPolicies"}">{translate key="schedConf.trackPolicies"}</a></li>
			{if $schedConfShowProgram}<li>&#187; <a href="{url page="schedConf" op="program"}">{translate key="schedConf.program"}</a></li>{/if}
			<li>&#187; <a href="{url page="schedConf" op="presentations"}">{translate key="schedConf.presentations.short"}</a></li>
			{if $schedConfPaymentsEnabled}<li>&#187; <a href="{url page="schedConf" op="registration"}">{translate key="schedConf.registration"}</a></li>{/if}
			<li>&#187; <a href="{url page="about" op="organizingTeam"}">{translate key="schedConf.supporters"}</a></li>
		</ul>
		{/if}

		{if $currentConference}
		<span class="blockSubtitle">{translate key="navigation.browse"}</span>
		<ul>
			<li><a href="{url page="search" op="schedConfs"}">{translate key="navigation.browseByConference"}</a></li>
			<li><a href="{url page="search" op="presenters"}">{translate key="navigation.browseByPresenter"}</a></li>
			<li><a href="{url page="search" op="titles"}">{translate key="navigation.browseByTitle"}</a></li>
			{if $hasOtherConferences}
			<li><a href="{url conference="index"}">{translate key="navigation.otherConferences"}</a></li>
			{/if}
		</ul>
		{/if}
	</div>
	
	<div class="block">
		<span class="blockTitle">{translate key="navigation.fontSize"}</span>
		<a href="#" onclick="setFontSize('{translate key="icon.small.alt"}');">{icon name="small"}</a>&nbsp;
		<a href="#" onclick="setFontSize('{translate key="icon.medium.alt"}');">{icon name="medium"}</a>&nbsp;
		<a href="#" onclick="setFontSize('{translate key="icon.large.alt"}');">{icon name="large"}</a>
	</div>

	{if $currentConference}
	{assign var=forReadersConference value=$currentConference->getSetting('readerInformation')}
	{assign var=forPresentersConference value=$currentConference->getSetting('presenterInformation')}
	{if !empty($forReadersConference) && !empty($forPresentersConference) || !empty($forReadersSchedConf) || !empty($forPresentersSchedConf)}
		<div class="block">
			<span class="blockTitle">{translate key="navigation.info"}</span>
			<ul>
				{if !empty($forReadersConference)}<li><a href="{url page="information" op="readers"}">{translate key="navigation.infoForReaders"}</a></li>{/if}
				{if !empty($forPresentersConference)}<li><a href="{url page="information" op="presenters"}">{translate key="navigation.infoForPresenters"}</a></li>{/if}
			</ul>
		</div>
	{/if}
	{/if}
</div>
