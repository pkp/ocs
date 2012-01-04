{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference setup index/intro.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.schedConfSetup.schedConfSetup"}
{include file="common/header.tpl"}
{/strip}

<span class="instruct">{translate key="manager.schedConfSetup.stepsToSchedConf"}</span>

<ol>
	<li>
		<h4><a href="{url op="schedConfSetup" path="1"}">{translate key="manager.schedConfSetup.details"}</a></h4>
		{translate key="manager.schedConfSetup.details.pageDescription"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="2"}">{translate key="manager.schedConfSetup.submissions"}</a></h4>
		{translate key="manager.schedConfSetup.submissions.pageDescription"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="3"}">{translate key="manager.schedConfSetup.review"}</a></h4>
		{translate key="manager.schedConfSetup.review.pageDescription"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
