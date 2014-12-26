{*capture name=path}{l s='Shipping'}{/capture*}
{*include file="$tpl_dir./breadcrumb.tpl"*}
{$HOOK_LEFT_COLUMN=false}
{$HOOK_LEFT_COLUMN=null}

<h2>{l s='Método de pago' mod='compropago'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}


<img src="{$base_dir}modules/compropago/images/logo-large.png" style=""> 

<div class="cp-select-form">
	<h2 style="font-size: 15px;line-height: 30px;padding-bottom: 8px;">Completar orden de pago</h2>
		<hr class="cp-grey" style="margin: 0px 0px 10px 0px;">

	<form action="{$this_path_ssl}validation.php" method="post">

	<span class="cp-wrap-price">
		{l s='Valor total del pedido:' mod='compropago'}
		{if $currencies|@count > 1}
			{foreach from=$currencies item=currency}
				<span id="amount_{$currency.id_currency}" class="price" style="display:none;">$ {$total}</span>
			{/foreach}
		{else}
				<span id="amount_{$currencies.0.id_currency}" class="cp-price" >$ {$total}</span>
		{/if}
	</span>
	<div class="cp-box-instructions">
		<label for="payment_type" class="cp-label-instructions"><b>Seleccione el establecimiento de pago:</b></label>
		<div class="cp-select-instructions">
			<select name="payment_type" id="payment_type">
			{foreach from=$payment_types key=key item=item}
				<option value="{$key}">{$item}</option>
			{/foreach}
			</select>
		</div>
	</div>
		<div class="cp-warning-box-price">{l s='Por favor confirme el establecimiento soportado por ComproPago y confirme su compra haciendo clic en "Confirmar Compra"' mod='compropago'}.</div>

</div>




<p class="cart_navigation">
	<a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Otras formas de pago' mod='compropago'}</a>
	<input type="submit" name="submit" value="{l s='Confirmar Compra' mod='compropago'}" class="exclusive_large" />
</p>
</form>