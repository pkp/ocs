{**
 * submitHeader.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for the paper submission pages.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="author.submit"}
{include file="common/header.tpl"}
{/strip}

<ul class="steplist">

	{if $showAbstractSteps}
		<li{if $submitStep == 1} class="current"{/if}>
			{if $submitStep != 1 && $submissionProgress >= 1}<a href="{url op="submit" path="1" paperId=$paperId}">{/if}
			{translate key="author.submit.start"}{if $submitStep != 1 && $submissionProgress >= 1}</a>{/if}
		</li>

		{if $showPaperSteps}
			<li{if $submitStep == 2} class="current"{/if}>
				{if $submitStep != 2 && $submissionProgress >= 2}<a href="{url op="submit" path="2" paperId=$paperId}">{/if}
				{translate key="author.submit.upload"}{if $submitStep != 2 && $submissionProgress >= 2}</a>{/if}
			</li>
		{else}
			<li{if $submitStep == 3} class="current"{/if}>
				{if $submitStep != 3 && $submissionProgress >= 3}<a href="{url op="submit" path="3" paperId=$paperId}">{/if}
				{translate key="author.submit.metadataOnly"}{if $submitStep != 3 && $submissionProgress >= 3}</a>{/if}
			</li>
		{/if}
		
		{if $currentSchedConf->getSetting('acceptSupplementaryReviewMaterials') && !$showPaperSteps}
			<li{if $submitStep == 4} class="current"{/if}>
				{if $submitStep != 4 && $submissionProgress >= 4}<a href="{url op="submit" path="4" paperId=$paperId}">{/if}
				{translate key="author.submit.supplementaryFilesAbstractOnly"}{if $submitStep != 4 && $submissionProgress >= 4}</a>{/if}
			</li>

			<li{if $submitStep == 5} class="current"{/if}>
				{if $submitStep != 5 && $submissionProgress >= 5}<a href="{url op="submit" path="5" paperId=$paperId}">{/if}
				{translate key="author.submit.confirmationSkipSupp"}{if $submitStep != 5 && $submissionProgress >= 5}</a>{/if}
			</li>
		{/if}
	{/if}

	{if $showPaperSteps}
		{if !$showAbstractSteps}
			<li{if $submitStep == 2} class="current"{/if}>
				{if $submitStep != 2 && $submissionProgress >= 2}<a href="{url op="submit" path="2" paperId=$paperId}">{/if}
				{translate key="author.submit.uploadOnly"}{if $submitStep != 2 && $submissionProgress >= 2}</a>{/if}
			</li>
		{else}
			<li{if $submitStep == 3} class="current"{/if}>
				{if $submitStep != 3 && $submissionProgress >= 3}<a href="{url op="submit" path="3" paperId=$paperId}">{/if}
				{translate key="author.submit.metadata"}{if $submitStep != 3 && $submissionProgress >= 3}</a>{/if}
			</li>
		{/if}

		{if $currentSchedConf->getSetting('acceptSupplementaryReviewMaterials')}
			<li{if $submitStep == 4} class="current"{/if}>
				{if $submitStep != 4 && $submissionProgress >= 4}<a href="{url op="submit" path="4" paperId=$paperId}">{/if}
				{translate key="author.submit.supplementaryFiles"}{if $submitStep != 4 && $submissionProgress >= 4}</a>{/if}
			</li>

			<li{if $submitStep == 5} class="current"{/if}>
				{if $submitStep != 5 && $submissionProgress >= 5}<a href="{url op="submit" path="5" paperId=$paperId}">{/if}
				{translate key="author.submit.confirmation"}{if $submitStep != 5 && $submissionProgress >= 5}</a>{/if}
			</li>
		{else}
			<li{if $submitStep == 5} class="current"{/if}>
				{if $submitStep != 5 && $submissionProgress >= 5}<a href="{url op="submit" path="5" paperId=$paperId}">{/if}
				{translate key="author.submit.confirmationSkipSupp"}{if $submitStep != 5 && $submissionProgress >= 5}</a>{/if}
			</li>
		{/if}
	{/if}

</ul>
