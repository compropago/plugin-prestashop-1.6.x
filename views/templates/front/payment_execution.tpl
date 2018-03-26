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

{**
 * Seccion de Steps
 *}

<h2>{l s='Order summary' mod='compropago'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{**
 * Seccion de Link
 *}

<div class="cprow">
    <div class="cpcolumn">
        {capture name=path}
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='compropago'}">{l s='Checkout' mod='compropago'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='ComproPago payment' mod='compropago'}
        {/capture}
    </div>
</div>

{if $providers == 0}
<div class="cprow">
        <div class="cpcolumn">
            <div class="cpalert">
                <h1>{l s='¡Servicio temporalmente fuera de servicio!' mod='compropago'}</h1>
                <p>{l s='Para seleccionar otro método de pago de clic en el botón.' mod='compropago'}</p>
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="cpbutton">{l s='Other payment methods' mod='compropago'}</a>
            </div>
        </div>
    </div>
{else}

    {if isset($nbProducts) && $nbProducts <= 0}
        <div class="cprow">
            <div class="cpcolumn">
                <div class="cpalert">
                    {l s='Your shopping cart is empty.' mod='compropago'}
                </div>
            </div>
        </div>
    {else}

    {* SECCION DE RESUM DE COMPRA *}
   
    <form action="{$link->getModuleLink('compropago', 'validation', [], true)|escape:'html'}" method="post">
        <div class="cprow">
            <div class="cpcolumn">
            <br>
                <h4 style="color:#000">{l s="¿Dónde quieres pagar?<sup>*</sup>" d='Modules.Compropago.Shop'}</h4>
            </div>
        </div>

        <div class="cprow">
            <div class="cpcolumn">
                <div id="cppayment_store">
                <form action="{$link->getModuleLink('compropago', 'validation', [], true)|escape:'html'}" method="post">
                    <select name="compropagoProvider" class="providers_list">
                        {foreach from=$providers item=provider}
                            <option value="{$provider->internal_name}">{$provider->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="cppayment_text">
                <br><br>
                    <p style="font-size:12px; color: #8f8f8f"><sup>*</sup>Comisionistas <a href="https://compropago.com/legal/corresponsales_cnbv.pdf" target="_blank" style="font-size:12px; color: #8f8f8f; font-weight:bold">autorizados por la CNBV</a> como corresponsales bancarios.</p>
                </div> <br>
            </div>
        </div>

        <div class="cprow">
            <div class="cpcolumn" >
                <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="cpbutton">{l s='Other payment methods' mod='compropago'}</a>
                <input type="submit" class="cpbutton cpbutton-primary" value="{l s='I confirm my order' mod='compropago'}">
            </div>
        </div>
    </form>
    {/if}

{/if}


