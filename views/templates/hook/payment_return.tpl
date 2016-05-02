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
* @since 2.0.0
*}



{if $status == 'ok'}
    <div class="cprow">
        <div class="cpcolumn">
            <h2>{l s='Your order on %s is complete.' sprintf=$shop_name mod='compropago'}</h2>
        </div>
    </div>
    <div class="cprow">
        <div class="cpcolumn">
            <table class="cptable">
                <thead>
                    <tr>
                        <th style="width:30%;">Detalle</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Numero de orden</td>
                        <td>{$id_order}</td>
                    </tr>

                    {if isset($reference)}
                        <tr>
                            <td>Numero de referencia de la orden</td>
                            <td>{$reference}</td>
                        </tr>
                    {/if}

                    <tr>
                        <td>Monto total a pagar</td>
                        <td>{$total_to_pay}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <div class="cprow">
        <div class="cpcolumn">
            <blockquote>
                {l s='An email has been sent to you with this information.' mod='compropago'} <br>

                <strong>{l s='Your order will be sent as soon as we receive your payment.' mod='compropago'}</strong>

                <br>

                {l s='For any questions or for further information, please contact our' mod='compropago'}
                <a href="{$link->getPageLink('contact', true)|escape:'html'}">
                    {l s='customer service department.' mod='compropago'}
                </a>
            </blockquote>
        </div>
    </div>


    <div class="cprow">
        <div class="cpcolumn">
            {include file="$compropagoTpl"}
        </div>
    </div>

{else}
    <div class="cprow">
        <div class="cpcolumn">
            <div class="cpalert">
                {l s='We have noticed that there is a problem with your order. If you think this is an error, you can contact our' mod='compropago'}
                <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer service department.' mod='compropago'}</a>.
            </div>
        </div>
    </div>
{/if}
