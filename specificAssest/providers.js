/**
 * Created by Arthur on 10/11/16.
 */

function setBack(types) {
    var dropShops = document.querySelector("select.providers_list"),
        back = '';

    switch (types) {
        case 'OXXO':
            back = 'https://compropago.com/assets/print/receipt-oxxo-btn-mini.png';
            break;
        case 'SEVEN_ELEVEN':
            back = 'https://compropago.com/assets/print/receipt-seven-btn-mini.png';
            break;
        case 'COPPEL':
            back = 'https://compropago.com/assets/print/receipt-coppel-btn-mini.png';
            break;
        case 'chedraui':
            back = 'https://compropago.com/assets/print/receipt-chedraui-btn-mini.png';
            break;
        case 'EXTRA':
            back = 'https://compropago.com/assets/print/receipt-extra-btn-mini.png';
            break;
        case 'FARMACIA_ESQUIVAR':
            back = 'https://compropago.com/assets/print/receipt-esquivar-btn-mini.png';
            break;
        //case 'farmacia_benavides':
        //    back = 'https://compropago.com/assets/print/receipt-benavides-btn-mini.png';
        //    break;
        case 'ELEKTRA':
            back = 'https://compropago.com/assets/print/receipt-elektra-btn-mini.png';
            break;
        case 'CASA_LEY':
            back = 'https://compropago.com/assets/print/receipt-ley-btn-mini.png';
            break;
        case 'PITICO':
            back = 'https://compropago.com/assets/print/receipt-pitico-btn-mini.png';
            break;
        case 'TELECOMM':
            back = 'https://compropago.com/assets/print/receipt-telecomm-btn-mini.png';
            break;
        case 'FARMACIA_ABC':
            back = 'https://compropago.com/assets/print/receipt-abc-btn-mini.png';
            break;
    }

    dropShops.style.backgroundImage = 'url(\'' + back + '\')';
    //dropShops.style.backgroundSize = ''
}

function cleanSelections(){
    var dropShops = document.querySelectorAll("ul.providers_list label");

    for(x = 0; x < dropShops.length; x++){
        dropShops[x].classList.remove('provider_selected');
    }
}

window.onload = function(){

    var selectProviders = document.querySelector("select.providers_list");

    if(selectProviders){
        setBack(selectProviders.value);

        selectProviders.addEventListener('change', function(evt){
            elem = evt.target;
            setBack(elem.value);
        });
    }else{
        var listProviders = document.querySelectorAll("ul.providers_list label");

        for(x = 0; x < listProviders.length; x++){
            listProviders[x].addEventListener('click', function(){
                cleanSelections();
                this.classList.add('provider_selected');
            });
        }
    }

};