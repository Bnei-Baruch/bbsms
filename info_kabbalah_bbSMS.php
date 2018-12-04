<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/*
 var url = "http://api.inforu.co.il/inforufrontend/WebInterface/SendMessageByNumber.aspx?"
  url += "UserName=" + userName;
  url += "&Password=" + password;
  url += "&SenderCellNumber=" + sender;
  url += "&MessageString=" + message;
  url += "&CellNumber=";
  for (var i=0; i<phonesArray.length; i++)
  {
    url += phonesArray[i] + ";"
  }

  return url;
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class info_kabbalah_bbSMS extends CRM_SMS_Provider
{

    CONST MAX_SMS_CHAR = 459;

    /**
     * api type to use to send a message
     * @var    string
     */
    protected $_apiType = 'http';

    /**
     * provider details
     * @var    string
     */
    protected $_providerInfo = array();

    /**
     * Clickatell API Server Session ID
     *
     * @var string
     */
    protected $_sessionID = NULL;

    /**
     * Curl handle resource id
     *
     */
    protected $_ch;

    /**
     * Temporary file resource id
     * @var    resource
     */
    protected $_fp;

    protected $_messageType = array(
        'SMS_TEXT',
    );

    protected $_messageStatus = array(
        '1' => 'OK',
        '-1' => 'Failed',
        '-2' => 'Bad user name or password',
        '-6' => 'Recipients Data Not Exists',
        '-9' => 'Message Text Not Exists',
        '-11' => 'Illegal XML',
        '-13' => 'User Quota Exceeded',
        '-14' => 'Project Quota Exceeded',
        '-15' => 'Customer Quota Exceeded',
        '-16' => 'Wrong Date/Time',
        '-17' => 'Wrong Number Parameter',
        '-18' => 'No Valid Recepients',
        '-20' => 'Invalid Sender Number',
        '-21' => 'Invalid Sender Name',
        '-22' => 'User Blocked',
        '-26' => 'User Authentication Error',
        '-28' => 'Network Type Not Supported',
        '-29' => 'Not All Network Types Supported',
        '-30' => 'Invalid Sender Identification',
    );

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = array();

    /**
     * Constructor
     *
     * Create and auth a Clickatell session.
     *
     * @param array $provider
     * @param bool $skipAuth
     *
     * @return \info_kabbalah_bbSMS
     */
    function __construct($provider = array(), $skipAuth = FALSE)
    {
        // initialize vars
        $this->_apiType = CRM_Utils_Array::value('api_type', $provider, 'http');
        $this->_providerInfo = $provider;
        CRM_Core_Error::debug_log_message("bbSMS: constructor");

        /**
         * Reuse the curl handle
         */
        $this->_ch = curl_init();
        if (!$this->_ch || !is_resource($this->_ch)) {
            return PEAR::raiseError('Cannot initialise a new curl handle.');
        }

        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($this->_ch, CURLOPT_VERBOSE, 1);
        curl_setopt($this->_ch, CURLOPT_FAILONERROR, 1);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($this->_ch, CURLOPT_COOKIEJAR, "/dev/null");
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->_ch, CURLOPT_USERAGENT, 'CiviCRM - http://civicrm.org/');
    }

    /**
     * singleton function used to manage this object
     *
     * @param array $providerParams
     * @param bool $force
     * @return object
     * @static
     */
    static function &singleton($providerParams = array(), $force = FALSE)
    {
        $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
        $skipAuth = $providerID ? FALSE : TRUE;
        $cacheKey = (int)$providerID;

        if (!isset(self::$_singleton[$cacheKey]) || $force) {
            $provider = array();
            if ($providerID) {
                $provider = CRM_SMS_BAO_Provider::getProviderInfo($providerID);
            }
            self::$_singleton[$cacheKey] = new info_kabbalah_bbSMS($provider, $skipAuth);
        }
        return self::$_singleton[$cacheKey];
    }

    /**
     * Send an SMS Message via the Clickatell API Server
     *
     * @param $recipients
     * @param $header
     * @param $message
     * @param null $jobID
     * @param null $userID
     * @internal param \the $array message with a to/from/text
     *
     * @return mixed true on sucess or PEAR_Error object
     * @access public
     */
    function send($recipients, $header, $message, $jobID = NULL, $userID = NULL)
    {
        CRM_Core_Error::debug_log_message("bbSMS: send");

        if ($this->_apiType == 'http') {
            CRM_Core_Error::debug_log_message("bbSMS: send http");
            $url = $this->_providerInfo['api_url'];
            $phoneNumbers = implode(';', $recipients);
            $message_text = preg_replace( "/\r|\n/", "", $message); // remove line breaks
            $xml = '';
            $xml .= '<Inforu>' . PHP_EOL;
            $xml .= '  <User>' . PHP_EOL;
            $xml .= '    <Username>' . htmlspecialchars($this->_providerInfo['username']) . '</Username>' . PHP_EOL;
            $xml .= '    <Username>' . htmlspecialchars($this->_providerInfo['password']) . '</Username>' . PHP_EOL;
            $xml .= '  </User>' . PHP_EOL;
            $xml .= '  <Content Type="sms">' . PHP_EOL;
            //TODO: max of 460 characters, is probably not multi-lingual
            $xml .= '    <Message>' . htmlspecialchars($message_text) . '</Message>' . PHP_EOL;
            $xml .= '  </Content>' . PHP_EOL;
            $xml .= '  <Recipients Type="sms">' . PHP_EOL;
            $xml .= '    <PhoneNumber>' . htmlspecialchars($phoneNumbers) . '</PhoneNumber>' . PHP_EOL;
            $xml .= '  </Recipients>' . PHP_EOL;
            $xml .= '  <Settings Type="sms">' . PHP_EOL;
            $xml .= '    <Sender>' . htmlspecialchars($this->_providerInfo['api_params']['sender']) . '</Sender>' . PHP_EOL;
            $xml .= '  </Settings>' . PHP_EOL;
            $xml .= '</Inforu>' . PHP_EOL;
            CRM_Core_Error::debug_log_message("bbSMS: send xml: " . $xml);

            $postData = urlencode($xml);
            $response = $this->curl($url, $postData);
            CRM_Core_Error::debug_log_message("bbSMS: send curl: " . var_export($response, true));
            if ($response['error']) {
                $errorMessage = $response['error'];
                CRM_Core_Session::setStatus(ts($errorMessage), ts('Sending SMS Error'), 'error');
                // TODO: Should add a failed activity instead.
                CRM_Core_Error::debug_log_message($response . " - for one of: {$recipients}");
                return false;
            } else {
                $this->createActivity(0, $message, $header, $jobID, $userID);
                return true;
            }
        }
    }

    /**
     * Perform curl stuff
     *
     * @param   string  URL to call
     * @param   string  HTTP Post Data
     *
     * @return  mixed   HTTP response body or PEAR Error Object
     * @access    private
     */
    function curl($url, $postData)
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url . $postData);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, Civi::settings()->get('verifySSL') ? 2 : 0);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, Civi::settings()->get('verifySSL'));
        // return the result on success, FALSE on failure
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 36000);

        // Send the data out over the wire
        $response = curl_exec($this->_ch);
        curl_close($this->_ch);
        CRM_Core_Error::debug_log_message("bbSMS: curl: " . var_export($response), true);

        if (!$response) {
            $errorMessage = 'Error: "' . curl_error($this->_ch) . '" - Code: ' . curl_errno($this->_ch);
            CRM_Core_Session::setStatus(ts($errorMessage), ts('API Error'), 'error');
            CRM_Core_Error::debug_log_message("bbSMS: curl error " . $errorMessage);

            $response = array();
            $response['error'] = $errorMessage;
            return $response;
        }

        CRM_Core_Error::debug_log_message("bbSMS: curl before parsing ");
        $xmlparser = xml_parser_create();
        xml_parse_into_struct($xmlparser, $response, $values);
        xml_parser_free($xmlparser);
        CRM_Core_Error::debug_log_message("bbSMS: curl after parsing " . var_export($values, true));
        $result = array();
        for ($i = 0; $i < count($values); $i++) {
            $v = $values[$i];
            if ($v['tag'] == 'Status' && $v['type'] == 'completed') {
                $result['status'] = $v['value'];
            } elseif ($v['tag'] == 'Description' && $v['type'] == 'completed') {
                $result['description'] = $v['value'];
            }
        }
        CRM_Core_Error::debug_log_message("bbSMS: curl result " . var_export($result, true));
        return $result;
    }

    /**
     * Authenticate
     *
     * @return mixed true on sucess or PEAR_Error object
     * @access public
     * @since 1.1
     */
    function authenticate()
    {
        return TRUE;
    }

    /**
     * @param $url
     * @param $postDataArray
     * @param null $id
     *
     * @return object|string
     */
    function formURLPostData($url, &$postDataArray, $id = NULL) {
        $url = $this->_providerInfo['api_url'] . $url;
        // GK 13102017 - New API doesn't need this param
        // $postDataArray['session_id'] = $this->_sessionID;
        if ($id) {
            if (strlen($id) < 32 || strlen($id) > 32) {
                return PEAR::raiseError('Invalid API Message Id');
            }
            $postDataArray['apimsgid'] = $id;
        }
        return $url;
    }
}
