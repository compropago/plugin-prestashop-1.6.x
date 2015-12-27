{*
* Copyright 2015 Compropago.
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @author Rolando Lucio <rolando@compropago.com>
*
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="Regresar Checkout">{l s='Checkout' mod='compropago'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='ComproPago' mod='compropago'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='compropago'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='compropago'}</p>
{else}

<h3>{l s='Pago ComproPago' mod='compropago'}</h3>
<form action="{$link->getModuleLink('compropago', 'validation', [], true)|escape:'html'}" method="post">

<p>
	<img src="{$this_path_bw}logo-to-action.png" alt="{l s='Compropago' mod='compropago'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='Ha seleccionado pagar con ComproPago' mod='compropago'}
	<br/><br />
	{l s='Resumen de su orden' mod='compropago'}
</p>
<p>
{*
* Css base de compropago, habilitar si no hay conflicto de css
*<link rel="stylesheet" type="text/css" href="{$compropagoCss}">
*}
{include file="$compropagoTemplate"}
</p>

<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='compropago'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='compropago'}
    {/if}
</p>
<p>
	-
	{if $currencies|@count > 1}
		{l s='We allow several currencies to be sent via bank wire.' mod='compropago'}
		<br /><br />
		{l s='Choose one of the following:' mod='compropago'}
		<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
			{foreach from=$currencies item=currency}
				<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
			{/foreach}
		</select>
	{else}
		{l s='We allow the following currency to be sent via Compropago:' mod='compropago'}&nbsp;<b>{$currencies.0.name}</b>
		<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
	{/if}
</p>
<p>
	La Información de Pago se mostrara en la siguiente página' 
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order".' mod='compropago'}</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='compropago'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='compropago'}</a>
</p>
</form>
{/if}