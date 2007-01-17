{**
 * program.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Event program page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="event.program"}
{include file="common/header.tpl"}

{if $programFile}
	<span class="instruct">{translate key="event.program.programFileInstructions"}</span>
	<div>
		<a class="file" href="{$publicFilesDir}/{$programFile.uploadName}" target="_blank" alt="">{translate key="event.program.viewProgramFile"}</a>
	</div>
{/if}

<div class="separator"></div>

<div>{$program|escape|nl2br}</div>

{include file="common/footer.tpl"}
