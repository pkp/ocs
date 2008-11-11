{**
 * complete.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The submission process has been completed; notify the author.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="author.track"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="author.submit.submissionComplete" conferenceTitle=$conference->getConferenceTitle()}</p>

{* TODO: expedite handler is incomplete
{if $canExpedite}
	{url|assign:"expediteUrl" op="expediteSubmission" paperId=$paperId}
	{translate key="author.submit.expedite" expediteUrl=$expediteUrl}
{/if}
*}

<p>&#187; <a href="{url op="track"}">{translate key="author.track"}</a></p>

{include file="common/footer.tpl"}
