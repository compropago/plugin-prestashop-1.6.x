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
* @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
* @since 2.0.0
*}


{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='compropago'}">{l s='Checkout' mod='compropago'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='ComproPago payment' mod='compropago'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='compropago'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='compropago'}</p>
{else}


<h3>{l s='ComproPago payment' mod='compropago'}</h3>
<form action="{$link->getModuleLink('compropago', 'validation', [], true)|escape:'html'}" method="post">
	<p>
		<img src="{$this_path_compropago}logo-badge.png" alt="{l s='ComproPago' mod='compropago'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen to pay by ComproPago.' mod='compropago'}
		<br/><br />
		{l s='Here is a short summary of your order:' mod='compropago'}
	</p>
	<p style="margin-top:20px;">
		- {l s='The total amount of your order comes to:' mod='compropago'}
		<span id="amount" class="price">{displayPrice price=$total}</span>
		{if $use_taxes == 1}
			{l s='(tax incl.)' mod='compropago'}
		{/if}
	</p>
{*
*	<p>
*		-
*		{if isset($currencies) && $currencies|@count > 1}
*			{l s='We accept several currencies to receive payments by ComproPago.' mod='compropago'}
*			<br /><br />
*			{l s='Choose one of the following:' mod='compropago'}
*			<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
*			{foreach from=$currencies item=currency}
*				<option value="{$currency.id_currency}" {if isset($currencies) && $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
*			{/foreach}
*			</select>
*		{else}
*			{l s='We allow the following currencies to be sent by ComproPago:' mod='compropago'}&nbsp;<b>{$currencies.0.name}</b>
*			<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
*		{/if}
*	</p>
*}	
	<p>
		{include file="$compropagoTpl"}

		<script>

			function StylerProviders(){

				var that = this;

				this.labels = document.querySelectorAll(".compropagoProviderDesc");

				this.init = function(){
				    that.clickProvider();
				};

				this.clickProvider = function(){
				    for(count = 0; count < that.labels.length; count++){
						that.labels[count].addEventListener("click",function(evt){
							var image = evt.target;

							that.clearProviders();

							image.setAttribute("style",
								"border: solid 4px #00AAEF;"+
							    "cursor: pointer;"+
							    "opacity: 1;"+
							    "border-radius: 8px;"+
							    "-webkit-border-radius: 8px;"+
							    "-moz-border-radius: 8px;"+
							    "box-shadow: 0px 0px 10px 0px rgba(0,0,0,.3), 0px 0px 0px 4px rgba(0,170,239,1);"+
							    "-webkit-box-shadow: 0px 0px 10px 0px rgba(0,0,0,.3),0px 0px 0px 4px rgba(0,170,239,1);"+
							    "-moz-box-shadow: 0px 0px 10px 0px rgba(0,0,0,.3),0px 0px 0px 4px rgba(0,170,239,1);"
							);
						});
				    }
				};

				this.clearProviders = function(){
					for(count = 0; count < that.labels.length; count++){
						that.labels[count].childNodes[1].setAttribute("style","border: 0;");
					}
				};

			}


			document.onreadystatechange = new StylerProviders().init;

		</script>
	</p>
	<p>
		{l s='ComproPago payment information will be displayed on the next page.' mod='compropago'}
		<br /><br />
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'.' mod='compropago'}</b>
	</p>
	<p class="cart_navigation" id="cart_navigation">
		<input type="submit" value="{l s='I confirm my order' mod='compropago'}" class="exclusive_large"/>
		<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='compropago'}</a>
	</p>
</form>
{/if}
