{**
 * index.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference management index.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.conferenceSiteManagement"}
{include file="common/header.tpl"}

<h3>{translate key="manager.managementPages"}</h3>

{translate key="manager.managementPages.description"}

<ul class="plain">
	<li>&#187; <a href="{url op="setup"}">{translate key="manager.websiteManagement"}</a></li>
	<li>&#187; <a href="{url op="schedConfs" clearPageContext=1}">{translate key="manager.schedConfs"}</a></li>
	{if $announcementsEnabled}
		<li>&#187; <a href="{url op="announcements" clearPageContext=1}">{translate key="manager.announcements"}</a></li>
	{/if}
	<li>&#187; <a href="{url op="reviewForms"}">{translate key="manager.reviewForms"}</a></li>
	<li>&#187; <a href="{url op="emails" clearPageContext=1}">{translate key="manager.emails"}</a></li>
	<li>&#187; <a href="{url page="rtadmin"}">{translate key="manager.readingTools"}</a></li>
	<li>&#187; <a href="{url op="files"}">{translate key="manager.filesBrowser"}</a></li>
	<li>&#187; <a href="{url op="languages"}">{translate key="common.languages"}</a></li>
	<li>&#187; <a href="{url op="conferenceEventLog" clearPageContext=1}">{translate key="manager.conferenceEventLog"}</a></li>
	<li>&#187; <a href="{url op="plugins"}">{translate key="manager.plugins"}</a></li>
	{call_hook name="Templates::Manager::Index::ManagementPages"}
</ul>


<h3>{translate key="manager.currentConferences"}</h3>
{iterate from=schedConfs item=schedConf}
	<h4>{$schedConf->getFullTitle()}</h4>
	<ul class="plain">
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="schedConfSetup"}">{translate key="manager.schedConfSetup"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="timeline"}">{translate key="manager.timeline"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="tracks" clearPageContext=1}">{translate key="track.tracks"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="groups" clearPageContext=1}">{translate key="manager.groups"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="program"}">{translate key="manager.program"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="importexport"}">{translate key="manager.importExport"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="statistics"}">{translate key="manager.statistics"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="scheduler"}">{translate key="manager.scheduler"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="paymentSettings"}">{translate key="manager.payment.paymentSettings"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="accommodation"}">{translate key="manager.accommodation"}</a></li>
		<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" clearPageContext=1}">{translate key="manager.roles"}</a></li>
		<li>
			<h4>{translate key="manager.roles"}</h4>

			<ul class="plain">
				<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" path="directors" clearPageContext=1}">{translate key="user.role.directors"}</a></li>
				<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" path="trackDirectors" clearPageContext=1}">{translate key="user.role.trackDirectors"}</a></li>
				<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" path="reviewers" clearPageContext=1}">{translate key="user.role.reviewers"}</a></li>
				<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" path="presenters" clearPageContext=1}">{translate key="user.role.presenters"}</a></li>
				<li>&#187; <a href="{url schedConf=$schedConf->getPath() page="manager" op="people" path="readers" clearPageContext=1}">{translate key="user.role.readers"}</a></li>
				{call_hook name="Templates::Manager::Index::SchedConfRoles"}
			</ul>
		</li>
		{call_hook name="Templates::Manager::Index::SchedConfFuncs" schedConf=$schedConf}
</ul>
{/iterate}


<h3>{translate key="manager.users"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="all" clearPageContext=1}">{translate key="manager.people.allEnrolledUsers"}</a></li>
	<li>&#187; <a href="{url op="enrollSearch" clearPageContext=1}">{translate key="manager.people.allSiteUsers"}</a></li>
	{url|assign:"managementUrl" page="manager"}
	<li>&#187; <a href="{url op="createUser" source=$managementUrl}">{translate key="manager.people.createUser"}</a></li>
	<li>&#187; <a href="{url op="mergeUsers"}">{translate key="manager.people.mergeUsers"}</a></li>
	{call_hook name="Templates::Manager::Index::Users"}
</ul>


<h3>{translate key="manager.roles"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="people" path="managers" clearPageContext=1}">{translate key="user.role.managers"}</a></li>
	{call_hook name="Templates::Manager::Index::Roles"}
</ul>

{include file="common/footer.tpl"}
