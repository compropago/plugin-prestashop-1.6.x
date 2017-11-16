/**
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
 */
/**
 * Compropago
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 */

function StylerProviders(){
    var that = this;

    this.labels = document.querySelectorAll("ul.providers_list label img");

    this.init = function(){
        that.clickProvider();
        console.log(that.labels);
    };

    this.clickProvider = function(){
        for(count = 0; count < that.labels.length; count++){
            that.labels[count].addEventListener("click", function(evt){
                that.clearProviders();

                this.classList.add('provider_selected');
            });
        }
    };

    this.clearProviders = function(){
        for(count = 0; count < that.labels.length; count++){
            that.labels[count].classList.remove('provider_selected');
        }
    };

}


window.onload = function(){
    new StylerProviders().init();
};