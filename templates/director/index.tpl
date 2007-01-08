{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference management index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.conferenceSiteManagement"}
{include file="common/header.tpl"}

<h3>{translate key="director.managementPages"}</h3>

<ul class="plain">
	{if $announcementsEnabled}
		<li>&#187; <a href="{url op="announcements"}">{translate key="director.announcements"}</a></li>
	{/if}
	<li>&#187; <a href="{url op="files"}">{translate key="director.filesBrowser"}</a></li>
	{*<li>&#187; <a href="{url op="importexport"}">{translate key="director.importExport"}</a></li>*}
	<li>&#187; <a href="{url op="languages"}">{translate key="common.languages"}</a></li>
	<li>&#187; <a href="{url op="emails"}">{translate key="director.emails"}</a></li>
	<li>&#187; <a href="{url page="rtadmin"}">{translate key="director.readingTools"}</a></li>
	<li>&#187; <a href="{url op="events"}">{translate key="director.scheduledConferences"}</a></li>
	<li>&#187; <a href="{url op="setup"}">{translate key="director.conferenceSiteSetup"}</a></li>
	<li>&#187; <a href="{url op="plugins"}">{translate key="director.plugins"}</a></li>
	{call_hook name="Templates::Director::Index::ManagementPages"}
</ul>


<h3>{translate key="director.scheduledConferences"}</h3>
{iterate from=events item=event}
	<h4>{$event->getFullTitle()}</h4>
	<ul class="plain">
		{if $announcementsEnabled}
			<li>&#187; <a href="{url op="announcements"}">{translate key="director.announcements"}</a></li>
		{/if}
		<li>&#187; <a href="{url event=$event->getPath() page="director" op="tracks"}">{translate key="track.tracks"}</a></li>
		<li>&#187; <a href="{url event=$event->getPath() page="director" op="groups"}">{translate key="director.groups"}</a></li>
		<li>&#187; <a href="{url event=$event->getPath() page="director" op="eventSetup"}">{translate key="director.eventSetup"}</a></li>
		<li>&#187; <a href="{url event=$event->getPath() page="director" op="statistics"}">{translate key="director.statistics"}</a></li>
		{if $registrationEnabled}
			<li>&#187; <a href="{url event=$event->getPath() page="director" op="registration"}">{translate key="director.registration"}</a></li>
		{/if}
		<li>&#187; <a href="{url event=$event->getPath() page="director" op="people"}">{translate key="director.roles"}</a></li>
		{call_hook name="Templates::Director::Index::EventManagementPages"}
	</ul>
{/iterate}


<h3>{translate key="director.users"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="all"}">{translate key="director.people.allUsers"}</a></li>
	<li>&#187; <a href="{url op="createUser"}">{translate key="director.people.createUser"}</a></li>
	{*<li>&#187; <a href="{url op="mergeUsers"}">{translate key="director.people.mergeUsers"}</a></li>*}
	{call_hook name="Templates::Director::Index::Users"}
</ul>


<h3>{translate key="director.roles"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="directors"}">{translate key="user.role.directors"}</a></li>
	{call_hook name="Templates::Director::Index::Roles"}
</ul>

{include file="common/footer.tpl"}
