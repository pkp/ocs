{**
 * view.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a help topic.
 *
 * $Id$
 *}
{strip}
{translate|assign:applicationHelpTranslated key="help.ocsHelp"}
{include file="core:help/view.tpl"}
{/strip}
