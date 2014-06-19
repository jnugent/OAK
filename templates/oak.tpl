{**
 * templates/oak.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interface for OAK to be displayed on submission editing page
 *
 *}

<h3>{translate key="plugins.generic.oak.OAK"}</h3>

<div>
	<div>{translate key="plugins.generic.oak.contactName" authorFullName=$authorFullName}</div>
	<div>{translate key="plugins.generic.oak.contactEmail" authorEmail=$authorEmail}</div>
	<div>{translate key="plugins.generic.oak.oakId" oakId=$oakId}</div>
</div>
<br />

{if $publishedArticle}
		{if $submittedToOak}
			{translate key="plugins.generic.oak.submittedToOak" submittedToOak=$submittedToOak}<br /><br />
		{else}
		{translate key="plugins.generic.oak.publicationFee"}
			<form method="post" action="{$pluginUrl}">
				<input type="hidden" name="submissionId" value={$submissionId} />
				<input type="hidden" name="authorOAKId" value={$oakId} />
				<input type="text" name="price" />&nbsp;
				<select name="currencyCode">
					<option value="USD">{translate key="plugins.generic.oak.currencyUSD"}</option>
					<option value="EUR">{translate key="plugins.generic.oak.currencyEUR"}</option>
					<option value="GBP">{translate key="plugins.generic.oak.currencyGBP"}</option>
				</select>&nbsp;
				{translate key="plugins.generic.oak.discount"} <input type="text" name="discount" value="0" />
				<input type="submit" value="{translate key="plugins.generic.oak.submitArticleToOak"}" />
			</form>
		{/if}
{else}
	<br />{translate key="plugins.generic.oak.articleMustBePublished"}
{/if}

{$oakOutput}

<div class="separator"></div>
