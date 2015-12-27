{*
*
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
*
*  @author Rolando Lucio <rolando@compropago.com>
*  @copyright  2015 ComproPago
*  @license   http://www.apache.org/licenses/LICENSE-2.0
*  
*}

<p class="payment_module">
	<a href="{$link->getModuleLink('compropago', 'payment')|escape:'html'}" title="{l s='Pagar usando ComproPago' mod='compropago'}">
		<img src="{$this_path_bw}logo-to-action.png" alt="{l s='Pagar usando ComproPago' mod='compropago'}" width="86" height="49"/>
		{l s='Pagar con ComproPago' mod='compropago'}&nbsp;<span>{l s='(Pagos en OXXO, 7Eleven y muchas tiendas más)' mod='compropago'}</span>
	</a>
</p> 