{**
 * plugins/generic/oak/templates/settingsForm.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * OAK plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.oak.manager.oakSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="oakSettings">
<div id="description">{translate key="plugins.generic.oak.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisherId" required="true" key="plugins.generic.oak.manager.settings.publisherId"}</td>
		<td width="80%" class="value"><input type="text" name="publisherId" id="publisherId" value="{$publisherId|escape}" size="15" maxlength="25" class="textField" />
	</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="password" required="true" key="plugins.generic.oak.manager.settings.password"}</td>
		<td width="80%" class="value"><input type="text" name="password" id="password" value="{$password|escape}" size="15" maxlength="25" class="textField" />
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
