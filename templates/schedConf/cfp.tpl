{**
 * cfp.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference call-for-papers page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.cfp"}
{include file="common/header.tpl"}

<div>{$cfpMessage|nl2br}</div>

<a href="{url page="presenter" op="submit"}">{translate key="schedConf.cfp.submitHere"}</a>

{include file="common/footer.tpl"}
