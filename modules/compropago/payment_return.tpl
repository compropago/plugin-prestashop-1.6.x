{if $status == 'ok'}
    <center>
    <!-- <center>{$imgBanner}</center> -->
    </center>
    <br />
    <h3>{l s='¡Felicitaciones! Su pedido ha sido generado correctamente.' mod='compropago'}</h3>
    <p>{l s='El importe de la compra es:' mod='compropago'} <span class="price">{$totalApagar}</span></p>
    <p>{l s='Para realizar un pago utilize la siguiente información:' mod='compropago'}</p>
	<br />
	<h3>{$description}</h3>
	<ol>
		<li>{l s='Paso 1:' mod='compropago'} {$step_1}</li>
		<li>{l s='Paso 2:' mod='compropago'} {$step_2}</li>
		<li>{l s='Paso 3:' mod='compropago'} {$step_3}</li>
	</ul>
	<h3>{l s='Notas:' mod='compropago'}</h3>
	<ul>
		<li><p>{$note_extra_comition}</li>
		<li>{$note_expiration_date}</li>
		<li>{$note_confirmation}</li>
	</ul>
	<br />
    <p>{l s='Si tiene alguna pregunta por favor, utilice el' mod='compropago'}	<a href="{$base_dir}contact-form.php">{l s='Formulario de contacto' mod='cheque'}</a>.</p>
    <br />
    {$formcompropago}
{else}
    <p class="warning">
        {l s='Hubo alguna falla en la presentación de su solicitud. Por favor, póngase en contacto con nuestro Servicio de Atención' mod='compropago'} 
        <a href="{$base_dir}contact-form.php">{l s='atención al cliente' mod='compropago'}</a>.
    </p>
{/if}
