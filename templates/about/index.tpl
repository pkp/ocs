{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.aboutTheConference"}
{include file="common/header.tpl"}

{* Show list of current conferences if one wasn't supplied *}
{if not $showAboutEvent and not $currentEvents->eof()}
	<h3>{translate key="about.currentConferences"}</h3>
	<ul class="plain">
		{iterate from=currentEvents item=event}
			<li>&#187; <a href="{url event="$event->getPath()"}">{$event->getFullTitle()}</a></li>
		{/iterate}
	</ul>
{/if}

<h3>{translate key="about.people"}</h3>
<ul class="plain">
	{if not (empty($conferenceSettings.mailingAddress) && empty($conferenceSettings.contactName) && empty($conferenceSettings.contactAffiliation) && empty($conferenceSettings.contactMailingAddress) && empty($conferenceSettings.contactPhone) && empty($conferenceSettings.contactFax) && empty($conferenceSettings.contactEmail) && empty($conferenceSettings.supportName) && empty($conferenceSettings.supportPhone) && empty($conferenceSettings.supportEmail))}
		<li>&#187; <a href="{url op="contact"}">{translate key="about.contact"}</a></li>
	{/if}
	{if $showAboutEvent}
		<li>&#187; <a href="{url op="organizingTeam"}">{translate key="about.organizingTeam"}</a></li>
	{/if}
	{call_hook name="Templates::About::Index::People"}
</ul>

<h3>{translate key="about.policies"}</h3>
<ul class="plain">
	{if !empty($conferenceSettings.focusScopeDesc)}<li>&#187; <a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	<li>&#187; <a href="{url op="editorialPolicies" anchor="trackPolicies"}">{translate key="about.trackPolicies"}</a></li>
	{if !empty($conferenceSettings.reviewPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($conferenceSettings.pubFreqPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if !empty($conferenceSettings.openAccessPolicy) || !empty($conferenceSettings.enableDelayedOpenAccess) || !empty($conferenceSettings.enableAuthorSelfArchive)}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $conferenceSettings.enableLockss && !empty($conferenceSettings.lockssLicense)}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{if !empty($conferenceSettings.enableRegistration)}
		<li>&#187; <a href="{url op="registration"}">{translate key="about.registration"}</a></li>
	{/if}
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>{/if}
	{/foreach}
	{call_hook name="Templates::About::Index::Policies"}
</ul>

{if $showAboutEvent}
	<h3>{translate key="about.submissions"}</h3>
	<ul class="plain">
		<li>&#187; <a href="{url op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
		{if !empty($conferenceSettings.authorGuidelines)}<li>&#187; <a href="{url op="submissions" anchor="authorGuidelines"}">{translate key="about.authorGuidelines"}</a></li>{/if}
		{if !empty($conferenceSettings.copyrightNotice)}<li>&#187; <a href="{url op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
		{if !empty($conferenceSettings.privacyStatement)}<li>&#187; <a href="{url op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
		{call_hook name="Templates::About::Index::Submissions"}
	</ul>
{/if}

<h3>{translate key="about.other"}</h3>
<ul class="plain">
	{if not (empty($conferenceSettings.publisher) && empty($conferenceSettings.contributorNote) && empty($conferenceSettings.contributors) && empty($conferenceSettings.sponsorNote) && empty($conferenceSettings.sponsors))}<li>&#187; <a href="{url op="conferenceSponsorship"}">{translate key="about.conferenceSponsorship"}</a></li>{/if}
	<li>&#187; <a href="{url op="siteMap"}">{translate key="about.siteMap"}</a></li>
	<li>&#187; <a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a></li>
	{if $publicStatisticsEnabled}<li>&#187; <a href="{url op="statistics"}">{translate key="about.statistics"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Other"}
</ul>

{include file="common/footer.tpl"}
