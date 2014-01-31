{**
 * cfp.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference call-for-papers page.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="schedConf.cfp.title"}
{include file="common/header.tpl"}
{/strip}
<div id="cfp">
<p>{$cfpMessage|nl2br}</p>

{if $authorGuidelines != ''}
	<h3>{translate key="about.authorGuidelines"}</h3>
	<p>{$authorGuidelines|nl2br}</p>
{/if}

{if $acceptingSubmissions}
	<p>
		{translate key="author.submit.startHere"}<br/>
		<a href="{url page="author" op="submit" requiresAuthor=1}" class="action">{translate key="author.submit.startHereLink"}</a><br />
	</p>
{else}
	<p>
		{$notAcceptingSubmissionsMessage}
	</p>
{/if}
</div>
{include file="common/footer.tpl"}
