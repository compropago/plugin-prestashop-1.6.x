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

	<a href="https://www.compropago.com/comprobante/?confirmation_id={$compropagoData->id}" target="_blank">Consulta los detalles de la orden haciendo click <b>Aquí</b></a>
	<hr class="compropagoHr">
	
	vence: {php echo $compropagoData->id}
	
	<p>{$compropagoData->instructions->description}</p>
	<ol>
		<li>{$compropagoData->instructions->step_1}</li>
		<li>{$compropagoData->instructions->step_2}</li>
		<li>{$compropagoData->instructions->step_3}</li>
	</ol>
</div>