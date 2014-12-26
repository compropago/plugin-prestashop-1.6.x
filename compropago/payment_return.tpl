{if $status == 'ok'}
    <center>
    <!-- <center>{$imgBanner}</center> -->
    </center>
    <br />
    <h3>{l s='¡Felicitaciones! Su pedido ha sido generado correctamente.' mod='compropago'}</h3>
    <div class="cp-instruction-section">
      <div class="cp-title">Seguir los siguientes pasos:</div>

      <div class="cp-step-box">
        <div class="cp-step">
              <div class="cp-num">{l s='1.' mod='compropago'}</div> {$step_1}
        </div>
        <div class="cp-step">
              <div class="cp-num">{l s='2.' mod='compropago'}</div> {$step_2}
        </div>
        <div class="cp-step">
            <div class="cp-num">{l s='3.' mod='compropago'}</div> {$step_3}
        </div>
      </div>
      <hr class="cp-grey">
      <span class="cp-note" style="font-size:12px;color: #333;">Oxxo/7Eleven/Extra cobra en caja una comisión de $7.00/$8.00 por concepto de recepción de cobranza.</span>
    </div>

	<div class="cp-warning-box">
        <img src="{$base_dir}modules/compropago/images/warning.png" style="margin: -7px 0px 0px 0px;"> 
        <span style="font-size: 12px;"><b>Importante</b></span>
        <ul style="" class="cp-warning">
          	<li>{$note_extra_comition}</li>
			<li>{$note_expiration_date}</li>
			<li>El número de cuenta/tarjeta asignado es único por cada orden.</li>

        </ul>
    </div>
	<hr class="cp-grey">
    <p>{l s='Si tiene alguna pregunta por favor, utilice el' mod='compropago'}	<a href="{$base_dir}index.php?controller=contact" target="_blank"><b>{l s='Formulario de contacto' mod='cheque'}</b></a>.</p>
    {$formcompropago}
{else}

    <p class="warning">
        {l s='Hubo alguna falla en la presentación de su solicitud. Por favor, póngase en contacto con nuestro Servicio de Atención' mod='compropago'} 
        <a href="{$base_dir}index.php?controller=contact">{l s='atención al cliente' mod='compropago'}</a>.
    </p>
{/if}
