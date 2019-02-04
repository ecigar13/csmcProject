<?php


namespace App\Utils;


class SMSEmailGateway
{
    const CARRIER_EMAIL_GATEWAY_ADDRESSES = array(
        'AT&T' => 'txt.att.net',
        'Cricket' => 'mms.cricketwireless.net',
        'Metro PCS' =>'mymetropcs.com',
        'Sprint' => 'messaging.sprintpcs.com',
        'T-Mobile' => 'tmomail.net',
        'Verizon' => 'vtext.com',
        'US Cellular'=>'email.uscc.net',
        'Virgin Mobile' =>'vmobl.com'
    );

}