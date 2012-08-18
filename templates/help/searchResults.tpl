{**
 * templates/help/searchResults.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show help search results.
 *
 *}
{strip}
{translate|assign:applicationHelpTranslated key="help.ocsHelp"}
{include file="core:help/searchResults.tpl"}
{/strip}

