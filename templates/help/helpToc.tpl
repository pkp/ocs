{**
 * helpToc.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the help table of contents
 *
 * $Id$
 *}
{strip}
{translate|assign:applicationHelpTranslated key="help.ocsHelp"}
{include file="core:help/helpToc.tpl"}
{/strip}
