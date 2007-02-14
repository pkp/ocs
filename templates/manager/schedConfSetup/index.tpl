{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference setup index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.schedConfSetup"}
{include file="common/header.tpl"}

<span class="instruct">{translate key="manager.setup.stepsToSchedConf"}</span>

<ol>
	<li>
		<h4><a href="{url op="schedConfSetup" path="1"}">{translate key="manager.setup.details"}</a></h4>
		{translate key="manager.setup.details.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="2"}">{translate key="manager.setup.submissions"}</a></h4>
		{translate key="manager.setup.submissions.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="3"}">{translate key="manager.setup.review"}</a></h4>
		{translate key="manager.setup.review.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="4"}">{translate key="manager.setup.participation"}</a></h4>
		{translate key="manager.setup.participation.description"}<br/>
		&nbsp;
	</li>
	<li>
		<h4><a href="{url op="schedConfSetup" path="5"}">{translate key="manager.setup.look"}</a></h4>
		{translate key="manager.setup.look.description"}<br/>
		&nbsp;
	</li>
</ol>

{include file="common/footer.tpl"}
