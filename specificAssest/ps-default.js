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

    this.labels = document.querySelectorAll(".compropagoProviderDesc");

    this.init = function(){
        that.clickProvider();
    };

    this.clickProvider = function(){
        for(count = 0; count < that.labels.length; count++){
            that.labels[count].addEventListener("click",function(evt){
                var image = evt.target;

                that.clearProviders();

                image.setAttribute("style",
                    "cursor: pointer;"+
                    "opacity: 1;"+
                    "border-radius: 8px;"+
                    "-webkit-border-radius: 8px;"+
                    "-moz-border-radius: 8px;"+
                    "box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"+
                    "-webkit-box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"+
                    "-moz-box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"
                );
            });
        }
    };

    this.clearProviders = function(){
        for(count = 0; count < that.labels.length; count++){
            that.labels[count].childNodes[1].setAttribute("style","border: 0;");
        }
    };

}


window.onload = function(){
    new StylerProviders().init();
};