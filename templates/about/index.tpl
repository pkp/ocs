{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.aboutTheConference"}
{include file="common/header.tpl"}
{/strip}

{* Show list of current conferences if one wasn't supplied *}
{if not $showAboutSchedConf and not $currentSchedConfs->eof()}
	<h3>{translate key="about.currentConferences"}</h3>
	<ul class="plain">
		{iterate from=currentSchedConfs item=schedConf}
			<li>&#187; <a href="{url schedConf=$schedConf->getPath()}">{$schedConf->getFullTitle()}</a></li>
		{/iterate}
	</ul>
{/if}
<div id="people">
<h3>{translate key="about.people"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="contact"}">{translate key="about.contact"}</a></li>
	{if $showAboutSchedConf}
		<li>&#187; <a href="{url op="organizingTeam"}">{translate key="about.organizingTeam"}</a></li>
	{/if}
	{call_hook name="Templates::About::Index::People"}
</ul>
</div>
<div id="policies">
<h3>{translate key="about.policies"}</h3>
<ul class="plain">
	{if $showAboutSchedConf && $currentSchedConf->getLocalizedSetting('reviewPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('archiveAccessPolicy') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiveAccessPolicy"}">{translate key="about.archiveAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableDelayedOpenAccess || $conferenceSettings.enableAuthorSelfArchive}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableLockss && $currentConference->getLocalizedSetting('lockssLicense') != ''}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{if $showAboutSchedConf && $schedConfPaymentsEnabled}<li>&#187; <a href="{url op="registration"}">{translate key="about.registration"}</a></li>{/if}
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li>&#187; <a href="{url op="editorialPolicies" anchor=custom-$key}">{$customAboutItem.title|escape}</a></li>{/if}
	{/foreach}
	{call_hook name="Templates::About::Index::Policies"}
</ul>
</div>
<div id="submissions">
<h3>{translate key="about.submissions"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
	{if $currentSchedConf && $currentSchedConf->getLocalizedSetting('authorGuidelines') != ''}<li>&#187; <a href="{url op="submissions" anchor="authorGuidelines"}">{translate key="about.authorGuidelines"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('copyrightNotice') != ''}<li>&#187; <a href="{url op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if $currentConference->getLocalizedSetting('privacyStatement') != ''}<li>&#187; <a href="{url op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Submissions"}
</ul>
</div>
<div id="other">
<h3>{translate key="about.other"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="siteMap"}">{translate key="about.siteMap"}</a></li>
	<li>&#187; <a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a></li>
	{if $publicStatisticsEnabled}<li>&#187; <a href="{url op="statistics"}">{translate key="about.statistics"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Other"}
</ul>
</div>
{include file="common/footer.tpl"}
