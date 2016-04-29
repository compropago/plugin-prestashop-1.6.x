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

<div class="cprow">
    <div class="cpcolumn">
        <h2>{l s='Order summary' mod='compropago'}</h2>

        {assign var='current_step' value='payment'}
        {include file="$tpl_dir./order-steps.tpl"}
    </div>
</div>

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

{**
 * Despliegue de errores
 *}

{if isset($nbProducts) && $nbProducts <= 0}
    <div class="cprow">
        <div class="cpcolumn">
            <p class="warning">{l s='Your shopping cart is empty.' mod='compropago'}</p>
        </div>
    </div>
{else}


{* SECCION DE RESUM DE COMPRA *}

<div class="cprow">
    <div class="ten cpcolumns">
        <h3>Resumen - {l s='ComproPago payment' mod='compropago'}</h3>
    </div>
    <div class="two cpcolumns">
        <img src="{$this_path_compropago}logo-badge.png" alt="{l s='ComproPago' mod='compropago'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
    </div>
</div>

<form action="{$link->getModuleLink('compropago', 'validation', [], true)|escape:'html'}" method="post">
    <div class="cprow">
        <div class="cpcolumn">
            <h4>{l s='You have chosen to pay by ComproPago.' mod='compropago'}</h4>
            <hr>
            <h4>{l s='Here is a short summary of your order:' mod='compropago'}</h4>
        </div>
    </div>

    <div class="cprow">
        <div class="cpcolumn">
            <table class="cptable">
                <thead>
                    <tr>
                        <th style="width: 60%">Detalle</th>
                        <th>Total</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{l s='The total amount of your order comes to:' mod='compropago'}</td>
                        <td>{displayPrice price=$total}</td>
                        <td>
                            {if $use_taxes == 1}
                                {l s='(tax incl.)' mod='compropago'}
                            {/if}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="cprow">
        <div class="cpcolumn">
            <h4>Seleccion de tienda</h4>
        </div>
    </div>

    <div class="cprow">
        <div class="cpcolumn">
            {include file="$compropagoTpl"}
        </div>
    </div>


    <div class="cprow">
        <div class="cpcolumn" style="text-align: center">
            <h4>{l s='ComproPago payment information will be displayed on the next page.' mod='compropago'}</h4>
            <br>
        </div>
    </div>


    <div class="cprow">
        <div class="cpcolumn" style="text-align: center">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="cpbutton">{l s='Other payment methods' mod='compropago'}</a>
            <input type="submit" class="cpbutton cpbutton-primary" value="{l s='I confirm my order' mod='compropago'}">
        </div>
    </div>
</form>
{/if}
