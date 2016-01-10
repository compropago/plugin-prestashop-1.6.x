<?php
/*
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
 * Prestashop translation strings, language: EN
 * if string need to be changed remember to change the MD5 hash
 * @author Rolando Lucio <rolando@compropago.com>
 * @since 2.0.0
 */
global $_MODULE;
$_MODULE = array();

//[modulePath]/compropago.php 
$_MODULE['<{compropago}prestashop>compropago_51364ec58cec9ee5f07941677781c917'] = 'ComproPago';
$_MODULE['<{compropago}prestashop>compropago_689256bbd6f0a9e1b86e5cee6b7a038d'] = 'This module allows you to accept payments in Mexico stores like OXXO, 7Eleven and More.';
$_MODULE['<{compropago}prestashop>compropago_90c823bb4829305115ec38cce1386eb2'] = 'Are you sure you want to delete ComproPago?';
$_MODULE['<{compropago}prestashop>compropago_e058afb19ca0e428bc7f052bd54b666d'] = 'The Public Key and Private Key must be configured before using this module.';
$_MODULE['<{compropago}prestashop>compropago_a02758d758e8bec77a33d7f392eb3f8a'] = 'No currency has been set for this module.';
$_MODULE['<{compropago}prestashop>compropago_377d9accbf1d85ca08a54df79a775684'] = 'The Public Key is required';
$_MODULE['<{compropago}prestashop>compropago_d8fb7d857003959310012b1bbc7a63c5'] = 'The Private Key is required';
$_MODULE['<{compropago}prestashop>compropago_325eaa6c19dfbfdcbb36b6b4fc743fbe'] = 'The Mode is required';
$_MODULE['<{compropago}prestashop>compropago_c888438d14855d7d96a2724ee9c306bd'] = 'Settings updated';
$_MODULE['<{compropago}prestashop>compropago_8bdb2b157c2e8364f7251fb3053650d0'] = 'Pay by ComproPago';
$_MODULE['<{compropago}prestashop>compropago_62f628ae5e19ad33992d7386c48bfb89'] = 'ComproPago details';
$_MODULE['<{compropago}prestashop>compropago_37c5b6e7c4291021b6100a6754ae7ffe'] = 'Public Key';
$_MODULE['<{compropago}prestashop>compropago_d2560860c51f895a9871741f0805c39e'] = 'Private Key';
$_MODULE['<{compropago}prestashop>compropago_a5faa48a5a05f3b45c78f7c27f63d288'] = 'Get your keys at ComproPago';
$_MODULE['<{compropago}prestashop>compropago_889bdcebb4779a00387d204f74e1a44b'] = 'ComproPago Panel';
$_MODULE['<{compropago}prestashop>compropago_c9cc8cce247e49bae79f15173ce97354'] = 'Save';
$_MODULE['<{compropago}prestashop>compropago_650be61892bf690026089544abbd9d26'] = 'Mode';
$_MODULE['<{compropago}prestashop>compropago_13f77ac96d8b8d46bc553674bed34352'] = 'Are you on live or testing?,Change your Keys according to the mode';
$_MODULE['<{compropago}prestashop>compropago_a849a06e943c4fa48e32586816a93852'] = 'Live Mode';
$_MODULE['<{compropago}prestashop>compropago_74e20d91279df8e47e489e30bbb8e63f'] = 'Testing Mode';
$_MODULE['<{compropago}prestashop>compropago_6c4f89a480dc019becdd5437962c36ab'] = 'Invalid Keys, The Public Key and Private Key must be valid before using this module.';
$_MODULE['<{compropago}prestashop>compropago_22dc559b60080d881e8758013a6ad7a1'] = 'Your Store and Your ComproPago account are set to different Modes.';
$_MODULE['<{compropago}prestashop>compropago_d5c6a1ad57e8f1c556e4b98493fd4170'] = 'ComproPago ALERT:Your Keys are for a different Mode.';
$_MODULE['<{compropago}prestashop>compropago_d98319bc92c360fc70600470dc089470'] = 'Your Keys and Your ComproPago account are set to different Modes.';
$_MODULE['<{compropago}prestashop>compropago_0cf28c16a23da7a5f2922ac4190b5b1d'] = 'WARNING: ComproPago account is Running in TEST Mode';
$_MODULE['<{compropago}prestashop>compropago_980310da5d94b91918f46b5c0c54c6f3'] = 'WebHook';
$_MODULE['<{compropago}prestashop>compropago_8f1106577a0c591223cd8d7621eea525'] = 'Set this Url at ComproPago Panel to use it  to confirm to your store when a payment has been confirmed';
$_MODULE['<{compropago}prestashop>compropago_5fb63579fc981698f97d55bfecb213ea'] = 'Copy & Paste this Url to WebHooks section of your ComproPago Panel to recive instant notifications when a payment is confirmed';
$_MODULE['<{compropago}prestashop>compropago_ac8c251a631865e7f185b7b08facef97'] = 'Could not load ComproPago SDK instances.';

// payment gateway validation
$_MODULE['<{compropago}prestashop>validation_e2b7dec8fa4b498156dfee6e4c84b156'] = 'This payment method is not available.';

// [modulePath]/views/templates/hook/info.tpl
$_MODULE['<{compropago}prestashop>infos_689256bbd6f0a9e1b86e5cee6b7a038d'] = 'This module allows you to accept payments in Mexico stores like OXXO, 7Eleven and More.';
$_MODULE['<{compropago}prestashop>infos_e444fe40d43bccfad255cf62ddc8d18f'] = 'If the client chooses this payment method, the order status will change to \'Waiting for payment.\'';
$_MODULE['<{compropago}prestashop>infos_a9da016950bfa3ec48d35f3cd6d8f26c'] = 'ComproPago will confirm the order as soon as payment is received via WebHook.';

// [modulePath]/views/templates/hook/payment.tpl
$_MODULE['<{compropago}prestashop>payment_8bdb2b157c2e8364f7251fb3053650d0'] = 'Pay by ComproPago';
$_MODULE['<{compropago}prestashop>payment_4e1fb9f4b46556d64db55d50629ee301'] = '(order processing will be longer)';
$_MODULE['<{compropago}prestashop>payment_c59f69fbb2b7f6ea888bfd17427f086a'] = 'Pay by ComproPago.';

// [modulePath]/views/templates/front/payment_execution.tpl
$_MODULE['<{compropago}prestashop>payment_execution_644818852b4dd8cf9da73543e30f045a'] = 'Go back to the Checkout';
$_MODULE['<{compropago}prestashop>payment_execution_6ff063fbc860a79759a7369ac32cee22'] = 'Checkout';
$_MODULE['<{compropago}prestashop>payment_execution_7e541f1914894446854558949163476e'] = 'ComproPago payment';
$_MODULE['<{compropago}prestashop>payment_execution_f1d3b424cd68795ecaa552883759aceb'] = 'Order summary';
$_MODULE['<{compropago}prestashop>payment_execution_879f6b8877752685a966564d072f498f'] = 'Your shopping cart is empty.';
$_MODULE['<{compropago}prestashop>payment_execution_51364ec58cec9ee5f07941677781c917'] = 'ComproPago';
$_MODULE['<{compropago}prestashop>payment_execution_08dc40f83d32fdab5c231d83af3bd601'] = 'You have chosen to pay by ComproPago.';
$_MODULE['<{compropago}prestashop>payment_execution_c884ed19483d45970c5bf23a681e2dd2'] = 'Here is a short summary of your order:';
$_MODULE['<{compropago}prestashop>payment_execution_3b3b41f131194e747489ef93e778ed0d'] = 'The total amount of your order comes to:';
$_MODULE['<{compropago}prestashop>payment_execution_1f87346a16cf80c372065de3c54c86d9'] = '(tax incl.)';
$_MODULE['<{compropago}prestashop>payment_execution_7b1c6e78d93817f61f2b1bbc2108a803'] = 'We accept several currencies to receive payments by check.';
$_MODULE['<{compropago}prestashop>payment_execution_a7a08622ee5c8019b57354b99b7693b2'] = 'Choose one of the following:';
$_MODULE['<{compropago}prestashop>payment_execution_f73ad0f08052884ff465749bf48b55ce'] = 'We allow the following currencies to be sent by check:';
$_MODULE['<{compropago}prestashop>payment_execution_7135ff14c7931e1c8e9d33aff3dfc7f7'] = 'Check owner and address information will be displayed on the next page.';
$_MODULE['<{compropago}prestashop>payment_execution_52f64bc0164b0e79deaeaaaa7e93f98f'] = 'Please confirm your order by clicking \'I confirm my order\'.';
$_MODULE['<{compropago}prestashop>payment_execution_46b9e3665f187c739c55983f757ccda0'] = 'I confirm my order';
$_MODULE['<{compropago}prestashop>payment_execution_569fd05bdafa1712c4f6be5b153b8418'] = 'Other payment methods';
$_MODULE['<{compropago}prestashop>payment_execution_0881a11f7af33bc1b43e437391129d66'] = 'Please confirm your order by clicking \'I confirm my order\'';

$_MODULE['<{compropago}prestashop>payment_return_88526efe38fd18179a127024aba8c1d7'] = 'Your order on %s is complete.';
$_MODULE['<{compropago}prestashop>payment_return_61da27a5dd1f8ced46c77b0feaa9e159'] = 'Your check must include:';
$_MODULE['<{compropago}prestashop>payment_return_621455d95c5de701e05900a98aaa9c66'] = 'Payment amount.';
$_MODULE['<{compropago}prestashop>payment_return_9b8f932b1412d130ece5045ecafd1b42'] = 'Payable to the order of';
$_MODULE['<{compropago}prestashop>payment_return_9a94f1d749a3de5d299674d6c685e416'] = 'Mail to';
$_MODULE['<{compropago}prestashop>payment_return_e1c54fdba2544646684f41ace03b5fda'] = 'Do not forget to insert your order number #%d.';
$_MODULE['<{compropago}prestashop>payment_return_4761b03b53bc2b3bd948bb7443a26f31'] = 'Do not forget to insert your order reference %s.';
$_MODULE['<{compropago}prestashop>payment_return_610abe74e72f00210e3dcb91a0a3f717'] = 'An email has been sent to you with this information.';
$_MODULE['<{compropago}prestashop>payment_return_ffd2478830ca2f519f7fe7ee259d4b96'] = 'Your order will be sent as soon as we receive your payment.';
$_MODULE['<{compropago}prestashop>payment_return_0db71da7150c27142eef9d22b843b4a9'] = 'For any questions or for further information, please contact our';
$_MODULE['<{compropago}prestashop>payment_return_decce112a9e64363c997b04aa71b7cb8'] = 'customer service department.';
$_MODULE['<{compropago}prestashop>payment_return_9bdf695c5a30784327137011da6ef568'] = 'We have noticed that there is a problem with your order. If you think this is an error, you can contact our';
$_MODULE['<{compropago}prestashop>payment_return_d15feee53d81ea16269e54d4784fa123'] = 'We noticed a problem with your order. If you think this is an error, feel free to contact our';




return $_MODULE;
