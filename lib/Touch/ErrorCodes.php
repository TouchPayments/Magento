<?php
/**
 * Touch Payments 
 * 
 * Error codes for error responses
 * 
 * @copyright 2013 Check'n Pay Finance Pty Limited
 */
class Touch_ErrorCodes {

    const ERR_VALIDATION = -32001;
    const ERR_ORDER_NOT_FOUND = -32002;
    const ERR_AUTHENTICATION_FAILURE = -32003;
    const ERR_ITEM_WRONG_FORMAT = -32004;
    const ERR_NO_MULTIPLE_ITEMS = -32005;
    const ERR_WRONG_SMS_CODE = -32006;
    const ERR_INVALID_ADDRESS = -32007;
    const ERR_INVALID_CHARACTERS = -32008;
    const ERR_INVALID_POSTCODE_SUBURB_COMBINATION = -32009;
    const ERR_DEVICE_SCORE_TOO_LOW = -32010;
    const ERR_INTERNAL = -32000;
   
    
    public static $forHumans = array(
        self::ERR_VALIDATION => 'Validation Error',
        self::ERR_ORDER_NOT_FOUND => 'Order could not be found',
        self::ERR_AUTHENTICATION_FAILURE => 'Authentication failure',
        self::ERR_ITEM_WRONG_FORMAT => 'Order Items have the wrong format',
        self::ERR_NO_MULTIPLE_ITEMS => 'Multiple items not supported',
        self::ERR_WRONG_SMS_CODE => 'Wrong sms code provided',
        self::ERR_INVALID_ADDRESS => 'Invalid Address provided',
        self::ERR_INVALID_CHARACTERS => 'Invalid Characters used eg "<" or ">"',
        self::ERR_INVALID_POSTCODE_SUBURB_COMBINATION => 'Invalid Postcode and suburb Combination provided.',
        self::ERR_DEVICE_SCORE_TOO_LOW => 'Given device is not trustworthy.',
        self::ERR_INTERNAL => 'Intenral error'
    );
    
}