<?php

$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
//include(dirname(__FILE__).'/compropago.php');


// function deprecated
//if (!$cookie->isLogged())

if(!Context::getContext()->customer->isLogged())
    Tools::redirect('authentication.php?back=order.php');

$compropago = new compropago();
echo $compropago->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');

?>


