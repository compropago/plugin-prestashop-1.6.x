{capture name=path}{l s='Shipping'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='compropago'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Pago via ComproPago' mod='compropago'}</h3>


<form action="{$this_path_ssl}validation.php" method="post">
<p style="margin-top:20px;">
	{l s='Valor total del pedido:' mod='compropago'}
	{if $currencies|@count > 1}
		{foreach from=$currencies item=currency}
			<span id="amount_{$currency.id_currency}" class="price" style="display:none;">$ {$total}</span>
		{/foreach}
	{else}
			<span id="amount_{$currencies.0.id_currency}" class="price">$ {$total}</span>
	{/if}
</p>
<p style="margin-top:20px;">
	<p><label for="payment_type">Seleccione el m&eacute;todo de pago:</label></p>
	<select name="payment_type" id="payment_type">
	{foreach from=$payment_types key=key item=item}
		<option value="{$key}">{$item}</option>
	{/foreach}
	</select>
</p>
<p>
	<b>{l s='Por favor verifique en los métodos de pago aceptados por ComproPago y confirme su compra haciendo clic en "Confirmar Compra"' mod='compropago'}.</b>
</p>

<!-- <p>
	<center>{$imgBanner}</center>
</p> -->

<p class="cart_navigation">
	<a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Otras formas de pago' mod='compropago'}</a>
	<input type="submit" name="submit" value="{l s='Confirmar Compra' mod='compropago'}" class="exclusive_large" />
</p>
</form>