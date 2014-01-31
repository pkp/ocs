{**
 * complete.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the "complete" button for submissions.
 *
 * $Id$
 *}
<div id="complete">
<h3>{translate key="submission.complete"}</h3>

<form method="post" action="{url op="completePaper"}">
	<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
	{translate key="submission.complete.description"}
	<input name="complete" {if $submission->getStatus() == STATUS_PUBLISHED}disabled="disabled" {/if}type="submit" value="{translate key="submission.complete"}" class="button" />
	<input name="remove" {if $submission->getStatus() != STATUS_PUBLISHED}disabled="disabled" {/if}type="submit" value="{translate key="common.remove"}" class="button" />
</form>
</div>
