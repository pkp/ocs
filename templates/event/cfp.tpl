{**
 * cfp.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Event call-for-papers page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="event.cfp"}
{include file="common/header.tpl"}

<div>{$cfpMessage|nl2br}</div>

<a href="{url page="author" op="submit"}">{translate key="event.cfp.submitHere"}</a>

{include file="common/footer.tpl"}
