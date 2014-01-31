{**
 * overview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference overview page.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="schedConf.overview.title"}
{include file="common/header.tpl"}
{/strip}

<div>{$overview|nl2br}</div>

{include file="common/footer.tpl"}
