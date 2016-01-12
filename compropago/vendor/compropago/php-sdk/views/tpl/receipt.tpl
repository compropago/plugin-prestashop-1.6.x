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
* Receipt TPL template
* 
* @author Rolando Lucio <rolando@compropago.com>
* @since 1.0.1
*}

<div id="compropagoWrapper">
<hr class="compropagoHr">
<a href="https://www.compropago.com/comprobante/?confirmation_id={$compropagoData->id}" target="_blank">{$compropagoReceiptLink}</a>
<hr class="compropagoHr">

<h3>{$compropagoOrderTitle}</h3>
<p>{$compropagoData->instructions->description}</p>
<p>- {$compropagoData->instructions->step_1}</p>
<p>- {$compropagoData->instructions->step_2}</p>
<p>- {$compropagoData->instructions->step_3}</p>

{if isset($compropagoData->instructions->note_extra_comition)}
<p>- {$compropagoData->instructions->note_extra_comition}</p>
{/if}
{if isset($compropagoData->instructions->note_expiration_date)}
<p>- {$compropagoData->instructions->note_expiration_date}</p>
{/if}
{if isset($compropagoData->instructions->note_confirmation)}
<p>- {$compropagoData->instructions->note_confirmation}</p>
{/if}

<hr class="compropagoHr">
<a href="https://www.compropago.com/comprobante/?confirmation_id={$compropagoData->id}" target="_blank">{$compropagoReceiptLink}</a>
<hr class="compropagoHr">
</div>